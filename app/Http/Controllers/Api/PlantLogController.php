<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePlantLogRequest;
use App\Http\Requests\UpdatePlantLogRequest;
use App\Models\Tank;
use App\Models\TankPlant;
use App\Models\PlantLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlantLogController extends BaseApiController
{
    private function ensureTankOwner(Request $request, Tank $tank): void
    {
        if ((int)$tank->user_id !== (int)$request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function ensureTankPlantOwner(Request $request, TankPlant $tankPlant): void
    {
        $tank = $tankPlant->tank;
        if (!$tank) abort(404, 'Tank not found.');
        $this->ensureTankOwner($request, $tank);
    }

    private function formatLog(PlantLog $log): array
    {
        $url = null;
        if ($log->image_path) {
            $url = asset('storage/' . ltrim($log->image_path, '/'));
        }

        return [
            'id' => $log->id,
            'tank_plant_id' => $log->tank_plant_id,
            'logged_at' => optional($log->logged_at)->format('Y-m-d'),
            'height' => $log->height,
            'status' => $log->status,
            'note' => $log->note,
            'image_url' => $url,
            'deleted_at' => optional($log->deleted_at)->toISOString(),
        ];
    }

    public function tankPlants(Request $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        $view = (string) $request->query('view', 'active'); // active | trash

        $q = TankPlant::query()
            ->where('tank_id', $tank->id)
            ->with('plant:id,name');

        if ($view === 'trash') {
            $q->onlyTrashed();
        }

        $items = $q->orderByDesc('id')
            ->get()
            ->map(function ($tp) {
                return [
                    'id' => $tp->id,
                    'tank_id' => $tp->tank_id,
                    'plant_id' => $tp->plant_id,
                    'plant_name' => $tp->plant?->name,
                    'planted_at' => optional($tp->planted_at)->format('Y-m-d'),
                    'position' => $tp->position,
                    'note' => $tp->note,
                    'deleted_at' => optional($tp->deleted_at)->toISOString(),
                ];
            });

        return $this->success([
            'tank' => ['id' => $tank->id, 'name' => $tank->name],
            'view' => $view,
            'tank_plants' => $items,
        ]);
    }


    public function index(Request $request, TankPlant $tankPlant)
    {
        $this->ensureTankPlantOwner($request, $tankPlant);

        $range = (string)$request->query('range', '30'); // 30 | 90 | all
        $view  = (string)$request->query('view', 'active'); // active | trash

        $q = PlantLog::query()->where('tank_plant_id', $tankPlant->id);

        if ($view === 'trash') {
            $q->onlyTrashed();
        }

        if ($range !== 'all') {
            $days = max(1, (int)$range);
            $q->where('logged_at', '>=', now()->subDays($days)->toDateString());
        }

        $logs = $q->orderByDesc('logged_at')->limit(300)->get();

        return $this->success([
            'tank_plant' => [
                'id' => $tankPlant->id,
                'tank_id' => $tankPlant->tank_id,
                'plant_id' => $tankPlant->plant_id,
            ],
            'logs' => $logs->map(fn($l) => $this->formatLog($l))->values(),
        ]);
    }

    public function store(StorePlantLogRequest $request, TankPlant $tankPlant)
    {
        $this->ensureTankPlantOwner($request, $tankPlant);

        $data = $request->validated();

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('plant_logs', 'public');
        }

        $log = PlantLog::create([
            'tank_plant_id' => $tankPlant->id,
            'logged_at' => $data['logged_at'],
            'height' => $data['height'] ?? null,
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'image_path' => $path,
        ]);

        return $this->success($this->formatLog($log), 'Plant log created.');
    }

    public function update(UpdatePlantLogRequest $request, PlantLog $plantLog)
    {
        $tankPlant = $plantLog->tankPlant;
        if (!$tankPlant) abort(404, 'Tank plant not found.');
        $this->ensureTankPlantOwner($request, $tankPlant);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($plantLog->image_path) {
                Storage::disk('public')->delete($plantLog->image_path);
            }
            $plantLog->image_path = $request->file('image')->store('plant_logs', 'public');
        }

        $plantLog->fill([
            'logged_at' => $data['logged_at'] ?? $plantLog->logged_at,
            'height' => array_key_exists('height', $data) ? $data['height'] : $plantLog->height,
            'status' => array_key_exists('status', $data) ? $data['status'] : $plantLog->status,
            'note' => array_key_exists('note', $data) ? $data['note'] : $plantLog->note,
        ])->save();

        return $this->success($this->formatLog($plantLog->fresh()), 'Plant log updated.');
    }

    public function destroy(Request $request, PlantLog $plantLog)
    {
        $tankPlant = $plantLog->tankPlant;
        if (!$tankPlant) abort(404, 'Tank plant not found.');
        $this->ensureTankPlantOwner($request, $tankPlant);

        // soft delete: KHÔNG xoá ảnh để restore còn ảnh
        $plantLog->delete();

        return $this->success(null, 'Plant log deleted.');
    }

    public function restore(Request $request, string $plantLog)
    {
        $log = PlantLog::withTrashed()->with('tankPlant.tank')->findOrFail($plantLog);
        $tp = $log->tankPlant;
        if (!$tp) abort(404, 'Tank plant not found.');
        $this->ensureTankPlantOwner($request, $tp);

        if ($log->trashed()) $log->restore();

        return $this->success($this->formatLog($log->fresh()), 'Plant log restored.');
    }
}
