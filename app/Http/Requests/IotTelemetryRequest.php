<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class IotTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (!$this->has('temperature')) {
            if ($this->filled('temp')) {
                $merge['temperature'] = $this->input('temp');
            } elseif ($this->filled('temperature_c')) {
                $merge['temperature'] = $this->input('temperature_c');
            } elseif ($this->filled('temp_c')) {
                $merge['temperature'] = $this->input('temp_c');
            }
        }

        if (!$this->has('ph') && $this->filled('ph_value')) {
            $merge['ph'] = $this->input('ph_value');
        }

        if (!$this->has('no3') && $this->filled('no3_ppm')) {
            $merge['no3'] = $this->input('no3_ppm');
        }

        if (!$this->has('tds') && $this->filled('tds_ppm')) {
            $merge['tds'] = $this->input('tds_ppm');
        }

        if (!$this->has('ec')) {
            if ($this->filled('ec_ms')) {
                $merge['ec'] = $this->input('ec_ms');
            } elseif ($this->filled('conductivity')) {
                $merge['ec'] = $this->input('conductivity');
            }
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'recorded_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if (!$value) return;

                    try {
                        $t = Carbon::parse($value);
                        if ($t->gt(now()->addMinutes(5))) {
                            $fail('Recorded at cannot be far in the future.');
                        }
                    } catch (\Throwable $e) {
                        $fail('Recorded at is invalid.');
                    }
                },
            ],

            'temperature' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'ph'          => ['nullable', 'numeric', 'min:0', 'max:14'],
            'no3'         => ['nullable', 'numeric', 'min:0', 'max:200'],
            'tds'         => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'ec'          => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fields = ['temperature', 'ph', 'no3', 'tds', 'ec'];

            $hasAny = false;
            foreach ($fields as $field) {
                $value = $this->input($field);
                if ($value !== null && $value !== '') {
                    $hasAny = true;
                    break;
                }
            }

            if (!$hasAny) {
                $validator->errors()->add('telemetry', 'At least one telemetry value is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'temperature.numeric' => 'Temperature must be numeric.',
            'ph.numeric'          => 'pH must be numeric.',
            'no3.numeric'         => 'NO₃ must be numeric.',
            'tds.numeric'         => 'TDS must be numeric.',
            'ec.numeric'          => 'EC must be numeric.',
        ];
    }
}