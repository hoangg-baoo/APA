<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tank_id' => ['nullable', 'integer', 'exists:tanks,id'],
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],

            // NEW: file upload
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
