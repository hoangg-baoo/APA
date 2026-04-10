<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],

            'length_cm'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000', 'required_with:width_cm,height_cm'],
            'width_cm'      => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000', 'required_with:length_cm,height_cm'],
            'height_cm'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000', 'required_with:length_cm,width_cm'],

            // ✅ prevent negative / zero
            'volume_liters' => ['sometimes', 'nullable', 'numeric', 'gt:0'],

            'substrate'     => ['sometimes', 'nullable', 'in:aqua_soil,sand,gravel,nutrient_substrate,lava_rock,other'],
            'light'         => ['sometimes', 'nullable', 'in:low,medium,high'],

            'co2'           => ['sometimes', 'required', 'in:none,liquid,diy,pressurized'],

            'description'   => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
