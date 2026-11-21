<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulesRequest extends FormRequest
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
        if ($this->has('status')) {
            $status = strtolower((string) $this->input('status'));
            $this->merge(['status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active']);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'shift_id' => ['sometimes', 'integer', 'exists:shifts,id'],
            'work_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
