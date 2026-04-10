<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentifySessionCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'max:8192'],
            'tank_id' => ['nullable', 'integer', 'exists:tanks,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}