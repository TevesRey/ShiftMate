<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAbsenceRequest extends FormRequest
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
            'employee_id' => ['sometimes', 'string', 'exists:employees,id'],
            'absence_date' => ['sometimes', 'date'],
            'description' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:pending,approved,rejected'],
            'reviewed_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'reviewed_at' => ['sometimes', 'date'],
        ];
    }
}
