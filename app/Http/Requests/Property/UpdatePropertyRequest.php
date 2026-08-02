<?php

namespace App\Http\Requests\Property;

use App\Enum\AmenityScope;
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', Rule::enum(PropertyType::class)],
            'profile_image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'amenity_uuids' => ['sometimes', 'array'],
            'amenity_uuids.*' => [
                'uuid',
                'distinct',
                Rule::exists('amenities', 'uuid')->where(
                    fn ($query) => $query->whereIn('scope', [AmenityScope::PROPERTY->value, AmenityScope::BOTH->value])
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
            'name.required' => 'Property name is required.',
            'type.required' => 'Please select a property type.',
            'profile_image.image' => 'The profile image must be a valid image file.',
            'profile_image.max' => 'The profile image must not exceed 5MB.',
            'amenity_uuids.*.exists' => 'One or more selected amenities are not available for a property (they may be unit-only).',
            'amenity_uuids.*.distinct' => 'Duplicate amenities are not allowed.',
        ];
    }
}
