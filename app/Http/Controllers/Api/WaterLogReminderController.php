<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpsertWaterLogReminderRequest;
use App\Models\Tank;
use App\Models\WaterLogReminder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaterLogReminderController extends BaseApiController
{
    private function ensureTankOwner(Request $request, Tank $tank): void
    {
        if ((int)$tank->user_id !== (int)$request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function frequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'daily' => 'Daily',
            'every_3_days' => 'Every 3 days',
            'weekly' => 'Weekly',
            'biweekly' => 'Every 2 weeks',
            default => 'Weekly',
        };
    }

    private function stepByFrequency(Carbon $dt, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $dt->copy()->addDay(),
            'every_3_days' => $dt->copy()->addDays(3),
            'weekly' => $dt->copy()->addWeek(),
            'biweekly' => $dt->copy()->addWeeks(2),
            default => $dt->copy()->addWeek(),
        };
    }

    private function calculateNextDueAt(string $frequency, ?string $startDate, ?string $preferredTime): ?Carbon
    {
        $date = $startDate ? Carbon::parse($startDate) : now();
        $time = $preferredTime ?: '08:00';

        $next = Carbon::parse($date->toDateString() . ' ' . $time);

        while ($next->lt(now())) {
            $next = $this->stepByFrequency($next, $frequency);
        }

        return $next;
    }

    private function formatReminder(?WaterLogReminder $reminder, Tank $tank): array
    {
        if (!$reminder) {
            return [
                'tank_id' => $tank->id,
                'enabled' => false,
                'frequency' => 'weekly',
                'frequency_label' => 'Weekly',
                'preferred_time' => '08:00',
                'start_date' => now()->toDateString(),
                'next_due_at' => null,
                'next_due_at_text' => 'Disabled',
            ];
        }

        return [
            'tank_id' => $reminder->tank_id,
            'enabled' => (bool) $reminder->enabled,
            'frequency' => $reminder->frequency,
            'frequency_label' => $this->frequencyLabel($reminder->frequency),
            'preferred_time' => $reminder->preferred_time,
            'start_date' => optional($reminder->start_date)->toDateString(),
            'next_due_at' => optional($reminder->next_due_at)->toISOString(),
            'next_due_at_text' => $reminder->enabled && $reminder->next_due_at
                ? $reminder->next_due_at->format('Y-m-d H:i')
                : 'Disabled',
        ];
    }

    public function show(Request $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        $reminder = WaterLogReminder::query()
            ->where('tank_id', $tank->id)
            ->first();

        return $this->success($this->formatReminder($reminder, $tank));
    }

    public function upsert(UpsertWaterLogReminderRequest $request, Tank $tank)
    {
        $this->ensureTankOwner($request, $tank);

        $data = $request->validated();

        $enabled = (bool) ($data['enabled'] ?? false);
        $frequency = $data['frequency'] ?? 'weekly';
        $preferredTime = $data['preferred_time'] ?? '08:00';
        $startDate = $data['start_date'] ?? now()->toDateString();

        $nextDueAt = $enabled
            ? $this->calculateNextDueAt($frequency, $startDate, $preferredTime)
            : null;

        $reminder = WaterLogReminder::updateOrCreate(
            ['tank_id' => $tank->id],
            [
                'enabled' => $enabled,
                'frequency' => $frequency,
                'preferred_time' => $preferredTime,
                'start_date' => $startDate,
                'next_due_at' => $nextDueAt,
            ]
        );

        return $this->success(
            $this->formatReminder($reminder->fresh(), $tank),
            'Reminder settings saved.'
        );
    }
}