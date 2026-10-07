<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'max:255'],
            'schedule_id' => ['required', 'string', 'maax:255'],
            'absence_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'reviewed_by' => ['required', 'string', 'max:255'],
            'reviewed_at' => ['required', 'date'],
        ];
    }
}
