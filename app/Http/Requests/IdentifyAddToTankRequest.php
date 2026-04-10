<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyAddToTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tank_id'           => ['required', 'integer', 'exists:tanks,id'],
            'plant_id'          => ['required', 'integer', 'exists:plants,id'],
            'identify_image_id' => ['required', 'integer', 'exists:plant_images,id'],
        ];
    }
}
