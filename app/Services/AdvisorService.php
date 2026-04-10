<?php

namespace App\Services;

use App\Models\Tank;
use App\Models\WaterLog;

class AdvisorService // Khai báo class AdvisorService: service tạo "advice" dựa trên water log
{
    public function adviseWaterLog(Tank $tank, WaterLog $log): array
    {
        $items = [];

        $ph = $log->ph;
        $temp = $log->temperature;
        $no3 = $log->no3;

        $op = $log->other_params ?? [];
        $gh = $op['gh'] ?? null;
        $kh = $op['kh'] ?? null;

        // If user logs nothing -> remind
        if ($ph === null && $temp === null && $no3 === null && $gh === null && $kh === null) {
            $items[] = $this->info(
                'No measurements',
                'You did not enter pH / temperature / NO₃ / GH / KH. Please measure at least pH and temperature so the advisor can give accurate suggestions.'
            );
            return $this->wrap($items);
        }

        // pH rules
        if ($ph !== null) {
            if ($ph < 5.5 || $ph > 8.5) {
                $items[] = $this->danger(
                    'pH far out of range',
                    "Current pH = {$ph}. If this persists it may stress fish/shrimp/plants. Prioritize stability before making adjustments."
                );
            } elseif ($ph < 6.2) {
                $items[] = $this->warn(
                    'pH slightly low',
                    "pH = {$ph}. The water is a bit acidic. If you keep sensitive shrimp/fish, monitor fluctuations closely."
                );
            } elseif ($ph > 7.8) {
                $items[] = $this->warn(
                    'pH slightly high',
                    "pH = {$ph}. The water is a bit alkaline. If your plants/fish prefer acidic water, consider lowering it slowly and keeping it stable."
                );
            } else {
                $items[] = $this->ok(
                    'pH looks good',
                    "pH = {$ph}. This is a generally safe range for many common planted tanks."
                );
            }
        }

        // Temperature rules
        if ($temp !== null) {
            if ($temp < 18 || $temp > 32) {
                $items[] = $this->danger(
                    'Temperature is extreme',
                    "Temperature = {$temp}°C. This can easily cause shock. Check your heater/fan and adjust gradually."
                );
            } elseif ($temp < 22) {
                $items[] = $this->warn(
                    'Temperature slightly low',
                    "Temperature = {$temp}°C. If this is a tropical tank, consider increasing it slowly."
                );
            } elseif ($temp > 28) {
                $items[] = $this->warn(
                    'Temperature slightly high',
                    "Temperature = {$temp}°C. Higher temps can boost algae and reduce oxygen. Add aeration/cooling if needed."
                );
            } else {
                $items[] = $this->ok(
                    'Temperature looks good',
                    "Temperature = {$temp}°C. Suitable for most common planted tanks."
                );
            }
        }

        // NO3 rules
        if ($no3 !== null) {
            if ($no3 >= 80) {
                $items[] = $this->danger(
                    'NO₃ is very high',
                    "NO₃ = {$no3} ppm. Consider a water change (gradually), reduce feeding, and review filtration/fertilizer dosing."
                );
            } elseif ($no3 > 40) {
                $items[] = $this->warn(
                    'NO₃ is high',
                    "NO₃ = {$no3} ppm. Consider a small water change and monitor for the next 2–3 days."
                );
            } elseif ($no3 < 5) {
                $items[] = $this->info(
                    'NO₃ is low',
                    "NO₃ = {$no3} ppm. If plants grow slowly or pale, you may be low on nutrients (depending on your dosing routine)."
                );
            } else {
                $items[] = $this->ok(
                    'NO₃ looks good',
                    "NO₃ = {$no3} ppm. This is a common working range for planted tanks."
                );
            }
        }

        // GH / KH basic hints
        if ($gh !== null) {
            if ($gh < 3) $items[] = $this->info(
                'Low GH',
                "GH = {$gh}. Soft water. If you keep shrimp/fish that need minerals, consider remineralizing."
            );
            if ($gh > 12) $items[] = $this->info(
                'High GH',
                "GH = {$gh}. Hard water. Some plants/shrimp can be sensitive—watch how they respond."
            );
        }

        if ($kh !== null) {
            if ($kh < 2) $items[] = $this->info(
                'Low KH',
                "KH = {$kh}. pH may swing more easily. Avoid sudden pH adjustments."
            );
            if ($kh > 10) $items[] = $this->info(
                'High KH',
                "KH = {$kh}. Strong buffering; pH is usually harder to lower quickly."
            );
        }

        // Stability hint: last 7 days swing
        $last7 = WaterLog::query()
            ->where('tank_id', $tank->id)
            ->whereNull('deleted_at')
            ->where('logged_at', '>=', now()->subDays(7))
            ->orderBy('logged_at', 'asc')
            ->get(['ph', 'temperature']);

        if ($last7->count() >= 3) {
            $phMin = $last7->min('ph'); $phMax = $last7->max('ph');
            if ($phMin !== null && $phMax !== null && ($phMax - $phMin) >= 0.6) {
                $items[] = $this->warn(
                    'pH fluctuates',
                    'Your 7-day pH swing is relatively large. Prioritize stability (filtering, CO₂ consistency, regular water changes).'
                );
            }

            $tMin = $last7->min('temperature'); $tMax = $last7->max('temperature');
            if ($tMin !== null && $tMax !== null && ($tMax - $tMin) >= 2.5) {
                $items[] = $this->warn(
                    'Temperature fluctuates',
                    'Your 7-day temperature swing is relatively large. Check heater/fan behavior and avoid frequent on/off changes.'
                );
            }
        }

        if (!$items) {
            $items[] = $this->info(
                'Not enough data',
                'The log was saved, but there is not enough data for specific advice. Add pH / temperature / NO₃ for better suggestions.'
            );
        }

        return $this->wrap($items);
    }

    private function wrap(array $items): array
    {
        $level = 'ok';
        foreach ($items as $it) {
            if ($it['level'] === 'danger') { $level = 'danger'; break; }
            if ($it['level'] === 'warning') { $level = 'warning'; }
        }

        return [
            'overall' => $level,
            'items' => $items,
        ];
    }

    private function ok(string $title, string $message): array
    {
        return ['level' => 'ok', 'title' => $title, 'message' => $message];
    }

    private function warn(string $title, string $message): array
    {
        return ['level' => 'warning', 'title' => $title, 'message' => $message];
    }

    private function danger(string $title, string $message): array
    {
        return ['level' => 'danger', 'title' => $title, 'message' => $message];
    }

    private function info(string $title, string $message): array
    {
        return ['level' => 'info', 'title' => $title, 'message' => $message];
    }
}
