<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlantRequest extends FormRequest
// Khai báo class StorePlantRequest: validate dữ liệu khi tạo Plant (admin tạo plant library)
{
    public function authorize(): bool
    {
        return true; // (nếu bạn muốn chặn admin-only thì add middleware/authorize sau)
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            // ✅ block negative + realistic bounds
            'ph_min'   => ['nullable', 'numeric', 'min:0', 'max:14'],
            'ph_max'   => ['nullable', 'numeric', 'min:0', 'max:14'],
            'temp_min' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'temp_max' => ['nullable', 'numeric', 'min:0', 'max:40'],

            'light_level' => ['required', 'in:low,medium,high'],
            'difficulty'  => ['required', 'in:easy,medium,hard'],

            'image_sample' => ['nullable', 'string', 'max:2048'],
            'care_guide'   => ['nullable', 'string'],

            // upload file (optional)
            'image_file' => ['nullable', 'image', 'max:5120'], // 5MB
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // ✅ ensure min <= max (only when both provided & numeric)
            $phMin = $this->input('ph_min');
            $phMax = $this->input('ph_max');
            if ($phMin !== null && $phMax !== null && is_numeric($phMin) && is_numeric($phMax)) {
                if ((float)$phMin > (float)$phMax) {
                    $v->errors()->add('ph_min', 'pH min must be less than or equal to pH max.');
                }
            }

            $tMin = $this->input('temp_min');
            $tMax = $this->input('temp_max');
            if ($tMin !== null && $tMax !== null && is_numeric($tMin) && is_numeric($tMax)) {
                if ((float)$tMin > (float)$tMax) {
                    $v->errors()->add('temp_min', 'Temperature min must be less than or equal to temperature max.');
                }
            }
        });
    }
}
