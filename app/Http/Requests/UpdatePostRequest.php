<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes','required','string','max:255'],
            'content' => ['sometimes','required','string'],
            'image' => ['nullable','file','mimes:jpg,jpeg,png,webp','max:5120'],
            'remove_image' => ['nullable','boolean'],
        ];
    }
}
