<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use App\Models\User;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\\s+/', ' ', trim((string) $this->input('name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'role' => ['sometimes', 'in:employee'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $name = strtolower(preg_replace('/\\s+/', ' ', trim((string) $this->input('name'))));
            if ($name !== '' && User::whereRaw('LOWER(TRIM(full_name)) = ?', [$name])->exists()) {
                $validator->errors()->add('name', 'An account with this full name already exists. Please contact the administrator if this is your account.');
            }
        });
    }
}
