<?php

namespace App\Console\Commands;

use App\Models\IotDevice;
use App\Models\Sensor;
use App\Models\Tank;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class IotCreateDeviceCommand extends Command
{
    protected $signature = 'iot:create-device
                            {tank_id : ID của tank}
                            {name=ESP32 Sensor Hub : Tên device}
                            {--uid= : Device UID tùy chỉnh}
                            {--inactive : Tạo device ở trạng thái inactive}';

    protected $description = 'Create a demo IoT device for a tank and print the raw device key once.';

    public function handle(): int
    {
        $tankId = (int) $this->argument('tank_id');
        $name = (string) $this->argument('name');

        $tank = Tank::find($tankId);

        if (!$tank) {
            $this->error("Tank #{$tankId} not found.");
            return self::FAILURE;
        }

        $uid = trim((string) ($this->option('uid') ?: ('esp32-' . Str::lower(Str::random(8)))));

        if (IotDevice::where('device_uid', $uid)->exists()) {
            $this->error("Device UID '{$uid}' already exists.");
            return self::FAILURE;
        }

        $rawKey = Str::random(40);

        $device = IotDevice::create([
            'tank_id'         => $tank->id,
            'name'            => $name,
            'device_uid'      => $uid,
            'device_key_hash' => hash('sha256', $rawKey),
            'is_active'       => !$this->option('inactive'),
            'meta'            => [
                'created_from' => 'artisan_command',
            ],
        ]);

        $defaultSensors = [
            'temperature' => ['name' => 'Temperature sensor', 'unit' => '°C'],
            'ph'          => ['name' => 'pH sensor', 'unit' => 'pH'],
            'no3'         => ['name' => 'NO₃ sensor', 'unit' => 'ppm'],
            'tds'         => ['name' => 'TDS sensor', 'unit' => 'ppm'],
            'ec'          => ['name' => 'EC sensor', 'unit' => 'mS/cm'],
        ];

        foreach ($defaultSensors as $type => $meta) {
            Sensor::firstOrCreate(
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

        $this->info('IoT device created successfully.');
        $this->line("Tank: {$tank->name} (ID {$tank->id})");
        $this->line("Device name: {$device->name}");
        $this->line("Device UID: {$device->device_uid}");
        $this->line("Raw device key: {$rawKey}");
        $this->warn('Copy the raw device key now. It will not be shown again.');

        $this->newLine();
        $this->info('Example Postman header:');
        $this->line('X-Device-Key: ' . $rawKey);

        $this->newLine();
        $this->info('Example JSON body:');
        $this->line(json_encode([
            'recorded_at' => now()->format('Y-m-d H:i:s'),
            'temperature' => 25.4,
            'ph' => 6.8,
            'no3' => 10,
            'tds' => 145,
            'ec' => 0.31,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}