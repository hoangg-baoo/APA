<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','max:255','confirmed'],

            'role' => ['nullable', Rule::in(['user','expert','admin'])],
            'status' => ['nullable', Rule::in(['active','blocked'])],
            'bio' => ['nullable','string'],
        ];
    }
}
