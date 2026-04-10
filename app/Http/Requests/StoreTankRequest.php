<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],

            // strict size fields: either all null or all valid > 0
            'length_cm'     => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:width_cm,height_cm'],
            'width_cm'      => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:length_cm,height_cm'],
            'height_cm'     => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:length_cm,width_cm'],

            // ✅ prevent negative / zero
            'volume_liters' => ['nullable', 'numeric', 'gt:0'],

            // dropdown values only
            'substrate'     => ['nullable', 'in:aqua_soil,sand,gravel,nutrient_substrate,lava_rock,other'],
            'light'         => ['nullable', 'in:low,medium,high'],

            // enum co2
            'co2'           => ['required', 'in:none,liquid,diy,pressurized'],

            'description'   => ['nullable', 'string', 'max:2000'],
        ];
    }
}
