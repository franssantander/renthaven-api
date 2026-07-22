<?php

namespace App\Http\Requests\Lease;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TerminateLeaseRequest extends FormRequest
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
            'move_out_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'deductions' => ['nullable', 'array'],
            'deductions.*.description' => ['required_with:deductions', 'string', 'max:255'],
            'deductions.*.amount' => ['required_with:deductions', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'move_out_date.required' => 'Please provide the tenant\'s move-out date.',
            'deductions.*.description.required_with' => 'Each deduction needs a short description.',
            'deductions.*.amount.required_with' => 'Each deduction needs an amount.',
        ];
    }
}
