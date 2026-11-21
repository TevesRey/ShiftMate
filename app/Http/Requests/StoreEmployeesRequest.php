<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeesRequest extends FormRequest
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
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:employees,user_id'],
            'employee_number' => ['required', 'integer', 'unique:employees,employee_number'],
            'position' => ['required', 'string', 'in:Barista,Cashier'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
