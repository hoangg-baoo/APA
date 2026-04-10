<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'max:5120'], // 5MB
            'top_k' => ['nullable', 'integer', 'min:1', 'max:10'],
            'note'  => ['nullable', 'string', 'max:2000'],
        ];
    }
}
