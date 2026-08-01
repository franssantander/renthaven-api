<?php

namespace App\Http\Requests\Lease;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaseRequest extends FormRequest
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
            'property_unit_uuid' => [
                'required',
                'uuid',
                Rule::exists('property_units', 'uuid')->whereNull('deleted_at'),
            ],
            'move_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'property_unit_uuid.required' => 'A property unit must be selected.',
            'property_unit_uuid.exists' => 'The selected property unit could not be found.',
            'move_date.date' => 'Please provide a valid move date.',
        ];
    }
}
