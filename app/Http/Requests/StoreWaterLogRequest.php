<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreWaterLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function waterLogRules(bool $partial = false): array
    {
        $prefix = $partial ? ['sometimes'] : [];

        return [
            'logged_at' => array_merge(
                $partial ? ['sometimes'] : [],
                [
                    'nullable',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (!$value) return;

                        try {
                            $t = Carbon::parse($value);

                            if ($t->gt(now()->addMinute())) {
                                $fail('Measured at cannot be in the future.');
                            }
                        } catch (\Throwable $e) {
                            $fail('Measured at is invalid.');
                        }
                    },
                ]
            ),

            'ph'          => array_merge($prefix, ['required', 'numeric', 'min:0', 'max:14']),
            'temperature' => array_merge($prefix, ['required', 'numeric', 'min:0', 'max:40']),
            'no3'         => array_merge($prefix, ['required', 'numeric', 'min:0', 'max:200']),

            'gh'  => array_merge($prefix, ['nullable', 'numeric', 'min:0', 'max:30']),
            'kh'  => array_merge($prefix, ['nullable', 'numeric', 'min:0', 'max:20']),
            'tds' => array_merge($prefix, ['nullable', 'numeric', 'min:0', 'max:5000']),
            'ec'  => array_merge($prefix, ['nullable', 'numeric', 'min:0', 'max:20']),

            'note' => array_merge($prefix, ['nullable', 'string', 'max:2000']),
        ];
    }

    public static function waterLogMessages(): array
    {
        return [
            'ph.required' => 'pH is required.',
            'temperature.required' => 'Temperature is required.',
            'no3.required' => 'NO₃ is required.',

            'ph.min' => 'pH cannot be negative.',
            'temperature.min' => 'Temperature cannot be negative.',
            'no3.min' => 'NO₃ cannot be negative.',
            'gh.min' => 'GH cannot be negative.',
            'kh.min' => 'KH cannot be negative.',
            'tds.min' => 'TDS cannot be negative.',
            'ec.min' => 'EC cannot be negative.',
        ];
    }

    public function rules(): array
    {
        return self::waterLogRules(false);
    }

    public function messages(): array
    {
        return self::waterLogMessages();
    }
}