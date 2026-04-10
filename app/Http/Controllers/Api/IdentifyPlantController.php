<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IdentifyAddToTankRequest;
use App\Http\Requests\IdentifyRegionRequest;
use App\Http\Requests\IdentifySessionAddToTankRequest;
use App\Http\Requests\IdentifySessionCreateRequest;
use App\Http\Requests\ImageSearchRequest;
use App\Models\IdentifyRegion;
use App\Models\IdentifySession;
use App\Models\Plant;
use App\Models\PlantImage;
use App\Models\Tank;
use App\Models\TankPlant;
use App\Services\ClipEmbeddingClient;
use App\Services\IdentifyAggregationService;
use App\Services\IdentifyRegionProposalClient;
use App\Services\PlantImageSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IdentifyPlantController extends BaseApiController
{
    public function __construct(
        private ClipEmbeddingClient $clip,
        private PlantImageSearchService $searcher,
        private IdentifyAggregationService $aggregator,
        private IdentifyRegionProposalClient $proposalClient
    ) {}

    private function ensureTankOwner(Request $request, Tank $tank): void
    {
        if ((int) $tank->user_id !== (int) $request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function ensureSessionOwner(Request $request, IdentifySession $session): void
    {
        if ((int) $session->user_id !== (int) $request->user()->id) {
            abort(403, 'You do not own this identify session.');
        }
    }

    private function publicStoragePath(string $stored): string
    {
        return 'storage/' . ltrim($stored, '/');
    }

    private function relativePublicStoragePath(string $publicPath): string
    {
        return ltrim((string) preg_replace('#^storage/#', '', $publicPath), '/');
    }

    private function absolutePublicStoragePath(string $publicPath): string
    {
        return storage_path('app/public/' . $this->relativePublicStoragePath($publicPath));
    }

    private function deleteCropFile(?string $publicPath): void
    {
        if (!$publicPath) {
            return;
        }

        Storage::disk('public')->delete($this->relativePublicStoragePath($publicPath));
    }

    private function saveCropBinary(string $binary, string $folder = 'manual'): string
    {
        $path = 'identify/sessions/regions/' . $folder . '/' . uniqid('', true) . '.png';
        Storage::disk('public')->put($path, $binary);

        return $this->publicStoragePath($path);
    }

    private function loadSessionRelations(IdentifySession $session): IdentifySession
    {
        return $session->load([
            'tank:id,name',
            'regions' => fn($q) => $q->orderByDesc('id'),
        ])->loadCount('regions');
    }

    private function formatRegion(IdentifyRegion $region): array
    {
        return [
            'id' => $region->id,
            'crop_image_path' => $region->crop_image_path,
            'crop_box' => $region->crop_box,
            'results' => $region->match_results['results'] ?? [],
            'proposal_source' => $region->proposal_source ?? 'manual',
            'proposal_score' => $region->proposal_score,
            'created_at' => optional($region->created_at)->toISOString(),
        ];
    }

    private function formatSessionSummary(IdentifySession $session): array
    {
        return [
            'id' => $session->id,
            'source_image_path' => $session->source_image_path,
            'note' => $session->note,
            'tank' => $session->relationLoaded('tank') && $session->tank
                ? [
                    'id' => $session->tank->id,
                    'name' => $session->tank->name,
                ]
                : null,
            'regions_count' => $session->regions_count
                ?? ($session->relationLoaded('regions') ? $session->regions->count() : 0),
            'merged_results' => $session->merged_results ?? [],
            'confirmed_plants' => $session->confirmed_plants ?? [],
            'created_at' => optional($session->created_at)->toISOString(),
        ];
    }

    private function formatSessionDetail(IdentifySession $session): array
    {
        return array_merge(
            $this->formatSessionSummary($session),
            [
                'regions' => $session->relationLoaded('regions')
                    ? $session->regions->map(fn($r) => $this->formatRegion($r))->values()
                    : [],
            ]
        );
    }

    private function recomputeMergedResults(IdentifySession $session): array
    {
        $session->loadMissing('regions');

        $merged = $this->aggregator->mergeTopPlants(
            $session->regions->pluck('match_results')->all(),
            10
        );

        $session->update([
            'merged_results' => $merged,
        ]);

        return $merged;
    }

    private function attachPlantsToTank(Tank $tank, array $plantIds, int $sessionId): array
    {
        $attached = [];

        foreach ($plantIds as $plantId) {
            $tankPlant = TankPlant::withTrashed()
                ->where('tank_id', $tank->id)
                ->where('plant_id', $plantId)
                ->first();

            if ($tankPlant) {
                if ($tankPlant->trashed()) {
                    $tankPlant->restore();
                }

                if (!$tankPlant->note) {
                    $tankPlant->update([
                        'note' => 'Added from Identify Session #' . $sessionId,
                    ]);
                }
            } else {
                $tankPlant = TankPlant::create([
                    'tank_id' => $tank->id,
                    'plant_id' => $plantId,
                    'note' => 'Added from Identify Session #' . $sessionId,
                ]);
            }

            $tankPlant->load('plant');
            $attached[] = $tankPlant;
        }

        return $attached;
    }

    private function clearExistingAutoRegions(IdentifySession $session): void
    {
        $autoRegions = $session->regions()
            ->where('proposal_source', 'auto')
            ->get();

        foreach ($autoRegions as $region) {
            $this->deleteCropFile($region->crop_image_path);
            $region->delete();
        }
    }

    // =========================
    // Legacy single-image flow
    // =========================

    public function search(ImageSearchRequest $request)
    {
        $user = $request->user();
        $file = $request->file('image');
        $topK = (int) ($request->input('top_k', 5));
        $note = $request->input('note');

        $stored = $file->store('identify', 'public');
        $publicPath = $this->publicStoragePath($stored);

        try {
            $vector = $this->clip->embedFromUploadedFile($file);
            $results = $this->searcher->search($vector, $topK);
        } catch (\Throwable $e) {
            $this->deleteCropFile($publicPath);
            return $this->fromException($e, 502);
        }

        if (count($results) === 0 || empty($results[0]['plant_id'])) {
            $this->deleteCropFile($publicPath);

            return $this->error(
                'No matches found. Please ensure library embeddings exist in plant_images.feature_vector.',
                422
            );
        }

        $history = PlantImage::create([
            'plant_id'       => (int) $results[0]['plant_id'],
            'image_path'     => $publicPath,
            'feature_vector' => null,
            'user_id'        => $user->id,
            'tank_id'        => null,
            'purpose'        => 'identify',
            'query_vector'   => $vector,
            'match_results'  => ['results' => $results],
            'note'           => $note,
        ]);

        return $this->success([
            'identify_image_id' => $history->id,
            'uploaded_image' => $publicPath,
            'results' => $results,
        ], 'Identify ok');
    }

    public function addToTank(IdentifyAddToTankRequest $request)
    {
        $user = $request->user();

        $tankId = (int) $request->input('tank_id');
        $plantId = (int) $request->input('plant_id');
        $identifyImageId = (int) $request->input('identify_image_id');

        $tank = Tank::findOrFail($tankId);

        if ((int) $tank->user_id !== (int) $user->id) {
            return $this->error('Forbidden: tank does not belong to you.', 403);
        }

        $history = PlantImage::query()
            ->where('id', $identifyImageId)
            ->where('purpose', 'identify')
            ->where('user_id', $user->id)
            ->first();

        if (!$history) {
            return $this->error('Identify history not found.', 404);
        }

        $tp = TankPlant::withTrashed()
            ->where('tank_id', $tank->id)
            ->where('plant_id', $plantId)
            ->first();

        if ($tp) {
            if ($tp->trashed()) {
                $tp->restore();
            }
        } else {
            $tp = TankPlant::create([
                'tank_id' => $tank->id,
                'plant_id' => $plantId,
                'note' => 'Added from Identify',
            ]);
        }

        $history->update([
            'tank_id' => $tank->id,
            'plant_id' => $plantId,
        ]);

        return $this->success([
            'tank_plant' => $tp->fresh(),
            'identify' => $history->fresh(),
        ], 'Added to tank');
    }

    // =========================
    // Phase 1.5 / 3 session flow
    // =========================

    public function createSession(IdentifySessionCreateRequest $request)
    {
        $tank = null;

        if ($request->filled('tank_id')) {
            $tank = Tank::findOrFail((int) $request->input('tank_id'));
            $this->ensureTankOwner($request, $tank);
        }

        $stored = $request->file('image')->store('identify/sessions/source', 'public');

        $session = IdentifySession::create([
            'user_id' => $request->user()->id,
            'tank_id' => $tank?->id,
            'source_image_path' => $this->publicStoragePath($stored),
            'note' => $request->input('note'),
            'merged_results' => [],
            'confirmed_plants' => [],
        ]);

        $session->load('tank')->loadCount('regions');

        return $this->success([
            'session' => $this->formatSessionSummary($session),
        ], 'Identify session created.');
    }

    public function addRegion(IdentifyRegionRequest $request, IdentifySession $session)
    {
        $this->ensureSessionOwner($request, $session);

        $file = $request->file('crop_image');
        $topK = (int) ($request->input('top_k', 5));

        $stored = $file->store('identify/sessions/regions/manual', 'public');
        $publicPath = $this->publicStoragePath($stored);

        try {
            $vector = $this->clip->embedFromUploadedFile($file);
            $results = $this->searcher->search($vector, $topK);
        } catch (\Throwable $e) {
            $this->deleteCropFile($publicPath);
            return $this->fromException($e, 502);
        }

        if (count($results) === 0) {
            $this->deleteCropFile($publicPath);

            return $this->error('No matches found for this selected region.', 422);
        }

        $region = IdentifyRegion::create([
            'identify_session_id' => $session->id,
            'crop_image_path' => $publicPath,
            'crop_box' => $request->input('crop_box'),
            'query_vector' => $vector,
            'match_results' => ['results' => $results],
            'proposal_source' => 'manual',
            'proposal_score' => null,
        ]);

        $this->loadSessionRelations($session);
        $this->recomputeMergedResults($session);

        $session->refresh();
        $this->loadSessionRelations($session);

        return $this->success([
            'region' => $this->formatRegion($region),
            'session' => $this->formatSessionDetail($session),
        ], 'Region identified.');
    }

    public function proposeRegions(Request $request, IdentifySession $session)
    {
        $this->ensureSessionOwner($request, $session);

        $validated = Validator::make($request->all(), [
            'max_regions' => ['nullable', 'integer', 'min:1', 'max:12'],
        ])->validate();

        $maxRegions = (int) ($validated['max_regions'] ?? 6);

        $sourceAbsolutePath = $this->absolutePublicStoragePath($session->source_image_path);

        if (!is_file($sourceAbsolutePath)) {
            return $this->error('Source image not found for this session.', 404);
        }

        try {
            $proposals = $this->proposalClient->proposeFromPath(
                $sourceAbsolutePath,
                basename($sourceAbsolutePath),
                $maxRegions
            );
        } catch (\Throwable $e) {
            return $this->fromException($e, 502);
        }

        if (count($proposals) === 0) {
            $this->loadSessionRelations($session);

            return $this->success([
                'created_count' => 0,
                'warning' => 'Auto detect found no region. Please try manual crop or use a clearer tank photo.',
                'session' => $this->formatSessionDetail($session),
            ], 'No auto regions found.');
        }

        $rowsToInsert = [];

        foreach ($proposals as $proposal) {
            $box = $proposal['box'] ?? null;
            $cropBase64 = $proposal['crop_base64'] ?? null;
            $proposalScore = isset($proposal['proposal_score'])
                ? (float) $proposal['proposal_score']
                : null;

            if (!is_array($box) || !is_string($cropBase64) || trim($cropBase64) === '') {
                continue;
            }

            $x = max(0, (int) ($box['x'] ?? 0));
            $y = max(0, (int) ($box['y'] ?? 0));
            $w = max(0, (int) ($box['w'] ?? 0));
            $h = max(0, (int) ($box['h'] ?? 0));

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $binary = base64_decode($cropBase64, true);

            if ($binary === false || strlen($binary) === 0) {
                continue;
            }

            $publicPath = $this->saveCropBinary($binary, 'auto');
            $absoluteCropPath = $this->absolutePublicStoragePath($publicPath);

            try {
                $vector = $this->clip->embedFromPath($absoluteCropPath, basename($absoluteCropPath));
                $results = $this->searcher->search($vector, 5);
            } catch (\Throwable $e) {
                $this->deleteCropFile($publicPath);
                continue;
            }

            if (count($results) === 0) {
                $this->deleteCropFile($publicPath);
                continue;
            }

            $rowsToInsert[] = [
                'identify_session_id' => $session->id,
                'crop_image_path' => $publicPath,
                'crop_box' => [
                    'x' => $x,
                    'y' => $y,
                    'w' => $w,
                    'h' => $h,
                ],
                'query_vector' => $vector,
                'match_results' => ['results' => $results],
                'proposal_source' => 'auto',
                'proposal_score' => $proposalScore,
            ];
        }

        if (count($rowsToInsert) === 0) {
            $this->loadSessionRelations($session);

            return $this->success([
                'created_count' => 0,
                'warning' => 'Auto detect could not create valid plant regions from this photo. Please use manual crop.',
                'session' => $this->formatSessionDetail($session),
            ], 'No valid auto regions created.');
        }

        try {
            $this->clearExistingAutoRegions($session);

            foreach ($rowsToInsert as $row) {
                IdentifyRegion::create($row);
            }
        } catch (\Throwable $e) {
            foreach ($rowsToInsert as $row) {
                $this->deleteCropFile($row['crop_image_path'] ?? null);
            }

            return $this->fromException($e, 500);
        }

        $this->loadSessionRelations($session);
        $this->recomputeMergedResults($session);

        $session->refresh();
        $this->loadSessionRelations($session);

        return $this->success([
            'created_count' => count($rowsToInsert),
            'session' => $this->formatSessionDetail($session),
        ], 'Auto-detected candidate regions.');
    }

    public function deleteRegion(Request $request, IdentifySession $session, IdentifyRegion $region)
    {
        $this->ensureSessionOwner($request, $session);

        if ((int) $region->identify_session_id !== (int) $session->id) {
            return $this->error('This region does not belong to the selected session.', 422);
        }

        $this->deleteCropFile($region->crop_image_path);
        $region->delete();

        $session->refresh();
        $this->loadSessionRelations($session);
        $this->recomputeMergedResults($session);

        $session->refresh();
        $this->loadSessionRelations($session);

        return $this->success([
            'session' => $this->formatSessionDetail($session),
        ], 'Region removed.');
    }

    public function showSession(Request $request, IdentifySession $session)
    {
        $this->ensureSessionOwner($request, $session);

        $this->loadSessionRelations($session);

        if (empty($session->merged_results)) {
            $this->recomputeMergedResults($session);
            $session->refresh();
            $this->loadSessionRelations($session);
        }

        return $this->success([
            'session' => $this->formatSessionDetail($session),
        ]);
    }

    public function addSessionToTankBatch(IdentifySessionAddToTankRequest $request, IdentifySession $session)
    {
        $this->ensureSessionOwner($request, $session);

        $tank = Tank::findOrFail((int) $request->input('tank_id'));
        $this->ensureTankOwner($request, $tank);

        $plantIds = collect($request->input('plants', []))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($plantIds) === 0) {
            return $this->error('Please select at least one plant.', 422);
        }

        $attached = $this->attachPlantsToTank($tank, $plantIds, $session->id);

        $confirmedPlants = Plant::query()
            ->whereIn('id', $plantIds)
            ->get(['id', 'name', 'difficulty', 'light_level', 'image_sample'])
            ->map(function ($p) {
                return [
                    'plant_id' => $p->id,
                    'name' => $p->name,
                    'difficulty' => $p->difficulty,
                    'light_level' => $p->light_level,
                    'image_sample' => $p->image_sample,
                ];
            })
            ->values()
            ->all();

        $session->update([
            'tank_id' => $tank->id,
            'confirmed_plants' => $confirmedPlants,
        ]);

        $session->refresh();
        $this->loadSessionRelations($session);

        return $this->success([
            'session' => $this->formatSessionDetail($session),
            'tank_plants' => collect($attached)->map(function ($tp) {
                return [
                    'id' => $tp->id,
                    'tank_id' => $tp->tank_id,
                    'plant_id' => $tp->plant_id,
                    'note' => $tp->note,
                ];
            })->values(),
        ], 'Selected plants added to tank.');
    }

    // =========================
    // History now session-based
    // =========================

    public function history(Request $request)
    {
        $user = $request->user();
        $tankId = $request->query('tank_id');

        $q = IdentifySession::query()
            ->where('user_id', $user->id)
            ->with(['tank:id,name'])
            ->withCount('regions')
            ->orderByDesc('created_at');

        if ($tankId !== null && $tankId !== '') {
            $tank = Tank::find($tankId);

            if (!$tank || (int) $tank->user_id !== (int) $user->id) {
                return $this->error('Invalid tank filter.', 422);
            }

            $q->where('tank_id', (int) $tankId);
        }

        $page = $q->paginate(12);

        $page->getCollection()->transform(function ($session) {
            return $this->formatSessionSummary($session);
        });

        return $this->success($page);
    }

    public function historyShow(Request $request, int $id)
    {
        $session = IdentifySession::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with([
                'tank:id,name',
                'regions' => fn($q) => $q->orderByDesc('id'),
            ])
            ->withCount('regions')
            ->first();

        if (!$session) {
            return $this->error('Not found.', 404);
        }

        if (empty($session->merged_results)) {
            $this->recomputeMergedResults($session);
            $session->refresh();
            $this->loadSessionRelations($session);
        }

        return $this->success([
            'session' => $this->formatSessionDetail($session),
        ]);
    }
}