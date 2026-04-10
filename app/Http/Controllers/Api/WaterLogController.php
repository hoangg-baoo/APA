<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ImportWaterLogsRequest;
use App\Http\Requests\StoreWaterLogRequest;
use App\Http\Requests\UpdateWaterLogRequest;
use App\Models\Tank;
use App\Models\WaterLog;
use App\Services\AdvisorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaterLogController extends BaseApiController
{
    private function ensureTankOwner(Request $request, Tank $tank): void
    {
        if ((int)$tank->user_id !== (int)$request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function formatLog(WaterLog $log): array
    {
        $op = $log->other_params ?? [];

        return [
            'id'            => $log->id,
            'tank_id'       => $log->tank_id,
            'logged_at'     => optional($log->logged_at)->format('Y-m-d H:i'),
            'logged_at_raw' => optional($log->logged_at)->toISOString(),
            'ph'            => $log->ph,
            'temperature'   => $log->temperature,
            'no3'           => $log->no3,
            'gh'            => $op['gh'] ?? null,
            'kh'            => $op['kh'] ?? null,
            'tds'           => $op['tds'] ?? null,
            'ec'            => $op['ec'] ?? null,
            'note'          => $op['note'] ?? null,
            'deleted_at'    => optional($log->deleted_at)->toISOString(),
        ];
    }

    private function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = $handle ? (string) fgets($handle) : '';
        if ($handle) {
            fclose($handle);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private function readCsvRows(string $path): array
    {
        $file = new \SplFileObject($path);
        $file->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );
        $file->setCsvControl($this->detectCsvDelimiter($path));

        $rows = [];
        foreach ($file as $row) {
            if ($row === false || $row === [null]) {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function canonicalCsvKey(?string $header): ?string
    {
        if ($header === null) {
            return null;
        }

        $clean = trim($header);
        $clean = preg_replace('/^\xEF\xBB\xBF/u', '', $clean);
        $clean = str_replace(['₃', '°', '℃'], ['3', '', 'c'], mb_strtolower($clean));
        $clean = preg_replace('/[^a-z0-9]+/u', '', $clean);

        return match ($clean) {
            'loggedat', 'measuredat', 'measuredtime', 'datetime', 'date' => 'logged_at',
            'ph' => 'ph',
            'temperature', 'temp', 'temperaturec', 'tempc' => 'temperature',
            'no3', 'nitrate', 'nitrates', 'no3ppm' => 'no3',
            'gh' => 'gh',
            'kh' => 'kh',
            'tds', 'tdsppm', 'totaldissolvedsolids' => 'tds',
            'ec', 'ecms', 'ecmscm', 'conductivity' => 'ec',
            'note', 'notes' => 'note',
            default => null,
        };
    }

    private function normalizeCsvValue(?string $value, ?string $key = null): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (in_array($key, ['ph', 'temperature', 'no3', 'gh', 'kh', 'tds', 'ec'], true)) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    public function index(Request $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        $range = (string)$request->query('range', '30');
        $view  = (string)$request->query('view', 'active');

        $q = WaterLog::query()->where('tank_id', $tank->id);

        if ($view === 'trash') {
            $q->onlyTrashed();
        }

        if ($range !== 'all') {
            $days = max(1, (int)$range);
            $q->where('logged_at', '>=', now()->subDays($days));
        }

        $logs = $q->orderByDesc('logged_at')->limit(200)->get();

        $last7 = WaterLog::query()
            ->where('tank_id', $tank->id)
            ->where('logged_at', '>=', now()->subDays(7));

        $avgPh7 = (clone $last7)->avg('ph');
        $tempMin7 = (clone $last7)->min('temperature');
        $tempMax7 = (clone $last7)->max('temperature');

        $latest = WaterLog::query()
            ->where('tank_id', $tank->id)
            ->orderByDesc('logged_at')
            ->first();

        return $this->success([
            'tank' => [
                'id' => $tank->id,
                'name' => $tank->name,
            ],
            'stats' => [
                'avg_ph_7d' => $avgPh7,
                'temp_min_7d' => $tempMin7,
                'temp_max_7d' => $tempMax7,
                'latest_no3' => $latest?->no3,
            ],
            'logs' => $logs->map(fn($l) => $this->formatLog($l))->values(),
        ]);
    }

    public function store(StoreWaterLogRequest $request, Tank $tank, AdvisorService $advisor)
    {
        $this->ensureTankOwner($request, $tank);

        $data = $request->validated();

        $other = [
            'gh' => $data['gh'] ?? null,
            'kh' => $data['kh'] ?? null,
            'tds' => $data['tds'] ?? null,
            'ec' => $data['ec'] ?? null,
            'note' => $data['note'] ?? null,
        ];

        $log = WaterLog::create([
            'tank_id'      => $tank->id,
            'logged_at'    => $data['logged_at'] ?? now(),
            'ph'           => $data['ph'] ?? null,
            'temperature'  => $data['temperature'] ?? null,
            'no3'          => $data['no3'] ?? null,
            'other_params' => $other,
        ]);

        $advice = $advisor->adviseWaterLog($tank, $log);

        return $this->success([
            'log' => $this->formatLog($log),
            'advice' => $advice,
        ], 'Water log created.');
    }

    public function import(ImportWaterLogsRequest $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        $path = $request->file('file')->getRealPath();
        $rows = $this->readCsvRows($path);

        if (count($rows) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'CSV must contain a header row and at least one data row.',
            ], 422);
        }

        $headers = array_map(
            fn($v) => is_string($v) ? $v : (string)$v,
            $rows[0]
        );

        $canonicalHeaders = array_map(fn($h) => $this->canonicalCsvKey($h), $headers);

        $required = ['ph', 'temperature', 'no3'];
        $missing = [];

        foreach ($required as $col) {
            if (!in_array($col, $canonicalHeaders, true)) {
                $missing[] = $col;
            }
        }

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required CSV columns: ' . implode(', ', $missing) . '.',
            ], 422);
        }

        $inserted = 0;
        $failedRows = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $payload = [];
            foreach ($canonicalHeaders as $index => $key) {
                if (!$key) {
                    continue;
                }
                $payload[$key] = $this->normalizeCsvValue($row[$index] ?? null, $key);
            }

            $hasAnyValue = false;
            foreach ($payload as $value) {
                if ($value !== null && $value !== '') {
                    $hasAnyValue = true;
                    break;
                }
            }

            if (!$hasAnyValue) {
                continue;
            }

            $validator = Validator::make(
                $payload,
                StoreWaterLogRequest::waterLogRules(false),
                StoreWaterLogRequest::waterLogMessages()
            );

            if ($validator->fails()) {
                $failedRows[] = [
                    'line' => $i + 1,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            $data = $validator->validated();

            WaterLog::create([
                'tank_id' => $tank->id,
                'logged_at' => $data['logged_at'] ?? now(),
                'ph' => $data['ph'],
                'temperature' => $data['temperature'],
                'no3' => $data['no3'],
                'other_params' => [
                    'gh' => $data['gh'] ?? null,
                    'kh' => $data['kh'] ?? null,
                    'tds' => $data['tds'] ?? null,
                    'ec' => $data['ec'] ?? null,
                    'note' => $data['note'] ?? null,
                ],
            ]);

            $inserted++;
        }

        return $this->success([
            'inserted_count' => $inserted,
            'failed_count' => count($failedRows),
            'failed_rows' => $failedRows,
        ], 'CSV import finished.');
    }

    public function update(UpdateWaterLogRequest $request, WaterLog $waterLog, AdvisorService $advisor)
    {
        $tank = $waterLog->tank;
        if (!$tank) abort(404, 'Tank not found.');
        $this->ensureTankOwner($request, $tank);

        $data = $request->validated();

        $other = $waterLog->other_params ?? [];
        if (array_key_exists('gh', $data)) $other['gh'] = $data['gh'];
        if (array_key_exists('kh', $data)) $other['kh'] = $data['kh'];
        if (array_key_exists('tds', $data)) $other['tds'] = $data['tds'];
        if (array_key_exists('ec', $data)) $other['ec'] = $data['ec'];
        if (array_key_exists('note', $data)) $other['note'] = $data['note'];

        $waterLog->fill([
            'logged_at'    => $data['logged_at'] ?? $waterLog->logged_at,
            'ph'           => array_key_exists('ph', $data) ? $data['ph'] : $waterLog->ph,
            'temperature'  => array_key_exists('temperature', $data) ? $data['temperature'] : $waterLog->temperature,
            'no3'          => array_key_exists('no3', $data) ? $data['no3'] : $waterLog->no3,
            'other_params' => $other,
        ])->save();

        $fresh = $waterLog->fresh();
        $advice = $advisor->adviseWaterLog($tank, $fresh);

        return $this->success([
            'log' => $this->formatLog($fresh),
            'advice' => $advice,
        ], 'Water log updated.');
    }

    public function destroy(Request $request, WaterLog $waterLog)
    {
        $tank = $waterLog->tank;
        if (!$tank) abort(404, 'Tank not found.');
        $this->ensureTankOwner($request, $tank);

        $waterLog->delete();

        return $this->success(null, 'Water log deleted.');
    }

    public function restore(Request $request, string $waterLog)
    {
        $log = WaterLog::withTrashed()->with('tank')->findOrFail($waterLog);
        $tank = $log->tank;
        if (!$tank) abort(404, 'Tank not found.');
        $this->ensureTankOwner($request, $tank);

        if ($log->trashed()) {
            $log->restore();
        }

        return $this->success($this->formatLog($log->fresh()), 'Water log restored.');
    }
}