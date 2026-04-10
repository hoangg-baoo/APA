<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IotTelemetryRequest;
use App\Models\IotDevice;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Tank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IotTelemetryController extends BaseApiController
{
    private function ensureTankOwner(Request $request, Tank $tank): void
    {
        if ((int) $tank->user_id !== (int) $request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function metricMap(): array
    {
        return [
            'temperature' => [
                'name' => 'Temperature sensor',
                'unit' => '°C',
            ],
            'ph' => [
                'name' => 'pH sensor',
                'unit' => 'pH',
            ],
            'no3' => [
                'name' => 'NO₃ sensor',
                'unit' => 'ppm',
            ],
            'tds' => [
                'name' => 'TDS sensor',
                'unit' => 'ppm',
            ],
            'ec' => [
                'name' => 'EC sensor',
                'unit' => 'mS/cm',
            ],
        ];
    }

    private function findAuthorizedDevice(Tank $tank, string $rawKey): ?IotDevice
    {
        $hash = hash('sha256', $rawKey);

        return IotDevice::query()
            ->where('tank_id', $tank->id)
            ->where('device_key_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    private function upsertSensor(IotDevice $device, string $type): Sensor
    {
        $meta = $this->metricMap()[$type];

        return Sensor::firstOrCreate(
            [
                'iot_device_id' => $device->id,
                'type' => $type,
            ],
            [
                'name' => $meta['name'],
                'unit' => $meta['unit'],
                'is_active' => true,
            ]
        );
    }

    private function buildLatestPayload(Tank $tank): array
    {
        $metricMap = $this->metricMap();

        $devices = IotDevice::query()
            ->where('tank_id', $tank->id)
            ->orderBy('name')
            ->get();

        $registeredDeviceNames = $devices->pluck('name')->filter()->values()->all();

        $latestRecordedAt = null;
        $liveDeviceNames = [];
        $readings = [];

        foreach ($metricMap as $type => $meta) {
            $reading = SensorReading::query()
                ->with(['sensor.iotDevice'])
                ->where('tank_id', $tank->id)
                ->where('type', $type)
                ->orderByDesc('recorded_at')
                ->orderByDesc('id')
                ->first();

            $readings[$type] = [
                'value'            => $reading?->numeric_value,
                'unit'             => $meta['unit'],
                'recorded_at'      => optional($reading?->recorded_at)->toISOString(),
                'recorded_at_text' => optional($reading?->recorded_at)->format('Y-m-d H:i'),
                'device_name'      => $reading?->sensor?->iotDevice?->name,
            ];

            if ($reading?->sensor?->iotDevice?->name) {
                $liveDeviceNames[] = $reading->sensor->iotDevice->name;
            }

            if ($reading?->recorded_at) {
                if (!$latestRecordedAt || $reading->recorded_at->gt($latestRecordedAt)) {
                    $latestRecordedAt = $reading->recorded_at;
                }
            }
        }

        $liveDeviceNames = array_values(array_unique($liveDeviceNames));

        $devicesText = !empty($liveDeviceNames)
            ? implode(', ', $liveDeviceNames)
            : (!empty($registeredDeviceNames) ? implode(', ', $registeredDeviceNames) : 'No device registered');

        return [
            'tank' => [
                'id' => $tank->id,
                'name' => $tank->name,
            ],
            'devices_count' => $devices->count(),
            'devices' => $devices->map(function ($device) {
                return [
                    'id'                => $device->id,
                    'name'              => $device->name,
                    'device_uid'        => $device->device_uid,
                    'is_active'         => (bool) $device->is_active,
                    'last_seen_at'      => optional($device->last_seen_at)->toISOString(),
                    'last_seen_at_text' => optional($device->last_seen_at)->format('Y-m-d H:i'),
                ];
            })->values(),
            'snapshot' => [
                'has_data'          => $latestRecordedAt !== null,
                'devices_text'      => $devicesText,
                'recorded_at'       => optional($latestRecordedAt)->toISOString(),
                'recorded_at_text'  => $latestRecordedAt ? $latestRecordedAt->format('Y-m-d H:i') : 'No telemetry yet',
                'values' => [
                    'temperature' => $readings['temperature']['value'] ?? null,
                    'ph'          => $readings['ph']['value'] ?? null,
                    'no3'         => $readings['no3']['value'] ?? null,
                    'tds'         => $readings['tds']['value'] ?? null,
                    'ec'          => $readings['ec']['value'] ?? null,
                ],
                'readings' => $readings,
            ],
        ];
    }

    public function store(IotTelemetryRequest $request, Tank $tank)
    {
        $rawKey = trim((string) $request->header('X-Device-Key', ''));

        if ($rawKey === '') {
            return $this->error('Missing X-Device-Key header.', 401);
        }

        $device = $this->findAuthorizedDevice($tank, $rawKey);

        if (!$device) {
            return $this->error('Invalid device key for this tank.', 403);
        }

        $data = $request->validated();
        $recordedAt = !empty($data['recorded_at'])
            ? Carbon::parse($data['recorded_at'])
            : now();

        $metricMap = $this->metricMap();
        $savedMetrics = [];

        DB::transaction(function () use ($tank, $device, $data, $recordedAt, $metricMap, &$savedMetrics) {
            foreach ($metricMap as $field => $meta) {
                if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                    continue;
                }

                $sensor = $this->upsertSensor($device, $field);

                SensorReading::create([
                    'tank_id'       => $tank->id,
                    'sensor_id'     => $sensor->id,
                    'type'          => $field,
                    'numeric_value' => (float) $data[$field],
                    'recorded_at'   => $recordedAt,
                    'raw_payload'   => $data,
                ]);

                $savedMetrics[] = $field;
            }

            $device->update([
                'last_seen_at' => now(),
            ]);
        });

        return $this->success([
            'saved_count'   => count($savedMetrics),
            'saved_metrics' => array_values($savedMetrics),
            'device' => [
                'id'                => $device->id,
                'name'              => $device->name,
                'device_uid'        => $device->device_uid,
                'last_seen_at'      => optional($device->fresh()->last_seen_at)->toISOString(),
                'last_seen_at_text' => optional($device->fresh()->last_seen_at)->format('Y-m-d H:i'),
            ],
            'snapshot' => $this->buildLatestPayload($tank)['snapshot'],
        ], 'Telemetry stored.');
    }

    public function latest(Request $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        return $this->success($this->buildLatestPayload($tank));
    }
}