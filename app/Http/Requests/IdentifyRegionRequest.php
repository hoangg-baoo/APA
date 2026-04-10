<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $cropBox = $this->input('crop_box');

        if (is_string($cropBox)) {
            $decoded = json_decode($cropBox, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge([
                    'crop_box' => $decoded,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'crop_image' => ['required', 'file', 'image', 'max:5120'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:10'],

            'crop_box' => ['nullable', 'array'],
            'crop_box.x' => ['nullable', 'numeric', 'min:0'],
            'crop_box.y' => ['nullable', 'numeric', 'min:0'],
            'crop_box.w' => ['nullable', 'numeric', 'min:1'],
            'crop_box.h' => ['nullable', 'numeric', 'min:1'],
        ];
    }
}