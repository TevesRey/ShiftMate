<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShifsRequest extends FormRequest
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
            'shift_name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'datetime'],
            'end_time' => ['required', 'datetime'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
