<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertWaterLogReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $time = $this->input('preferred_time');

        if (is_string($time)) {
            $time = trim($time);

            // nếu browser / DB trả HH:MM:SS thì cắt còn HH:MM
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
                $time = substr($time, 0, 5);
            }

            $this->merge([
                'preferred_time' => $time,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'every_3_days', 'weekly', 'biweekly'])],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['nullable', 'date'],
        ];
    }
}