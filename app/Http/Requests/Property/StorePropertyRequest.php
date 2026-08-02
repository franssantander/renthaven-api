<?php

namespace App\Http\Requests\Property;

use App\Enum\AmenityScope;
use App\Enum\PropertyType;
use App\Enum\Role;
use App\Models\Property;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $response = Gate::inspect('create', Property::class);
        if ($response->denied()) {
            throw new HttpResponseException(response()->json([
                'data' => null,
                'status' => 403,
                'message' => $response->message(),
            ], 403));
        }

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
            'tenant_business_uuid' => ['required', 'uuid', 'exists:tenant_businesses,uuid'],
            'name' => ['required', 'unique:properties,name', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'profile_image' => ['nullable', 'image', 'max:5120'],
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

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role?->slug !== Role::SUPER_ADMIN->value) {
            $this->merge(['tenant_business_uuid' => $this->user()?->tenantBusiness?->uuid]);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tenant_business_uuid.required' => 'A business must be selected for this property.',
            'tenant_business_uuid.exists' => 'The selected business could not be found.',
            'name.required' => 'Property name is required.',
            'name.unique' => 'A property with this name already exists.',
            'type.required' => 'Please select a property type.',
            'profile_image.image' => 'The profile image must be a valid image file.',
            'profile_image.max' => 'The profile image must not exceed 5MB.',
            'amenity_uuids.*.exists' => 'One or more selected amenities are not available for a property (they may be unit-only).',
            'amenity_uuids.*.distinct' => 'Duplicate amenities are not allowed.',
        ];
    }
}
