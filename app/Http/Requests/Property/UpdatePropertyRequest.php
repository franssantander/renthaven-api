<?php

namespace App\Http\Requests\Property;

use App\Enum\PropertyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
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
            'tenant_business_id' => ['sometimes', 'required', 'integer', 'exists:tenant_businesses,id'],
            'name'                => ['sometimes', 'required', 'unique:properties,name', 'string', 'max:255'],
            'address'             => ['sometimes', 'nullable', 'string', 'max:1000'],
            'type'                => ['sometimes', 'required', Rule::enum(PropertyType::class)],
        ];
    }
}