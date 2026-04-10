<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logged_at' => ['required', 'date'],
            'height'    => ['nullable', 'numeric'],
            'status'    => ['required', 'string', 'max:100'],
            'note'      => ['nullable', 'string', 'max:5000'],
            'image'     => ['nullable', 'image', 'max:2048'],
        ];
    }
}
