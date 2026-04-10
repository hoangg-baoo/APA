<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportWaterLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a CSV file.',
            'file.mimes' => 'The file must be a CSV or TXT file.',
            'file.max' => 'The CSV file must not exceed 2 MB.',
        ];
    }
}