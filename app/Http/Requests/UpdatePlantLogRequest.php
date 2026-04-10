<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logged_at' => ['sometimes', 'nullable', 'date'],
            'height'    => ['sometimes', 'nullable', 'numeric'],
            'status'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'note'      => ['sometimes', 'nullable', 'string', 'max:5000'],
            'image'     => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }
}
