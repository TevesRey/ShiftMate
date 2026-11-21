<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchedulesRequest extends FormRequest
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
    protected function prepareForValidation(): void
    {
        $status = strtolower((string) $this->input('status', 'active'));
        $this->merge(['status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active']);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'work_date' => ['required', 'date'],
            // Chosen by the administrator from the time dropdowns; overnight shifts (e.g. 22:00 to 06:00) are allowed.
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i,H:i:s', 'different:start_time'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
