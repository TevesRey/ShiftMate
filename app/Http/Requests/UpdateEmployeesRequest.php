<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Employee numbers and account links are assigned by administrators and immutable here.
        // The Edit Employee form is intentionally limited to position changes.
        return [
            'position' => ['required', 'string', 'in:Barista,Cashier'],
        ];
    }
}
