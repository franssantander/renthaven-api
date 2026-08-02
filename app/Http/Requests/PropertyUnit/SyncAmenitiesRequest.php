<?php

namespace App\Http\Requests\PropertyUnit;

use App\Enum\AmenityScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAmenitiesRequest extends FormRequest
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
            'amenity_uuids' => ['required', 'array'],
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
            'amenity_uuids.required' => 'Please provide at least one amenity.',
            'amenity_uuids.*.exists' => 'One or more selected amenities are not available for a unit (they may be property-only).',
            'amenity_uuids.*.distinct' => 'Duplicate amenities are not allowed.',
        ];
    }
}
