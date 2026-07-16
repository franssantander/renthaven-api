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
            'property_unit_id' => [
                'required',
                'integer',
                Rule::exists('property_units', 'id')->whereNull('deleted_at'),
            ],
            'move_date' => ['nullable', 'date'],
        ];
    }
}
