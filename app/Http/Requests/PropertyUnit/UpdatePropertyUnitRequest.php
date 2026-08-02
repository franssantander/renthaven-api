<?php

namespace App\Http\Requests\PropertyUnit;

use App\Enum\AmenityScope;
use App\Enum\PropertyUnitStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyUnitRequest extends FormRequest
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
        $propertyUnit = $this->route('propertyUnit');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('property_units', 'name')
                    ->where(fn ($query) => $query->where('property_id', $propertyUnit->property_id)->whereNull('deleted_at'))
                    ->ignore($propertyUnit->id),
            ],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'rent_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable', 'string', Rule::enum(PropertyUnitStatus::class)],
            'amenity_uuids' => ['sometimes', 'array'],
            'amenity_uuids.*' => [
                'uuid',
                'distinct',
                Rule::exists('amenities', 'uuid')->where(
                    fn ($query) => $query->whereIn('scope', [AmenityScope::UNIT->value, AmenityScope::BOTH->value])
                ),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Unit name is required.',
            'name.unique' => 'This property already has a unit with that name.',
            'capacity.min' => 'A unit must be able to house at least one tenant.',
            'rent_price.min' => 'Rent price cannot be negative.',
            'amenity_uuids.*.exists' => 'One or more selected amenities are not available for a unit (they may be property-only).',
            'amenity_uuids.*.distinct' => 'Duplicate amenities are not allowed.',
        ];
    }
}
