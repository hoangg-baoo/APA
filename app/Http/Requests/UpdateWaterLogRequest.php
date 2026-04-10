<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaterLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return StoreWaterLogRequest::waterLogRules(true);
    }

    public function messages(): array
    {
        return StoreWaterLogRequest::waterLogMessages();
    }
}