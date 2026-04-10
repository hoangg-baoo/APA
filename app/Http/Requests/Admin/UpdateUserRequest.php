<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($userId)],
            'bio' => ['sometimes','nullable','string'],

            // password optional khi edit
            'password' => ['sometimes','nullable','string','min:6','max:255','confirmed'],

            'role' => ['sometimes','required', Rule::in(['user','expert','admin'])],
            'status' => ['sometimes','required', Rule::in(['active','blocked'])],
        ];
    }
}
