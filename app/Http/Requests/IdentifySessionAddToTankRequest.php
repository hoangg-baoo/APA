<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentifySessionAddToTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],
            'plants' => ['required', 'array', 'min:1', 'max:10'],
            'plants.*' => ['required', 'integer', 'exists:plants,id'],
        ];
    }
}