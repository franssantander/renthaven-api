<?php

namespace App\Http\Requests\PropertyUnit;

use App\Enum\AmenityScope;
use App\Enum\PropertyUnitStatus;
use App\Models\PropertyUnit;
use App\Support\UuidResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePropertyUnitRequest extends FormRequest
{
    /**
     * Normalize a flat, single-unit request body into a `units` array of one,
     * so the endpoint accepts both a single unit and multiple units.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('units') && $this->has('name')) {
            $this->merge([
                'units' => [
                    [
                        'name' => $this->input('name'),
                        'capacity' => $this->input('capacity'),
                        'rent_price' => $this->input('rent_price'),
                        'status' => $this->input('status'),
                        'amenity_uuids' => $this->input('amenity_uuids'),
                    ],
                ],
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $response = Gate::inspect('create', [PropertyUnit::class, count($this->input('units', []))]);

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
        $tenantId = $this->user()?->tenant_business_id;
        $propertyId = UuidResolver::id('properties', $this->input('property_uuid'));

        return [
            'property_uuid' => [
                'required',
                'uuid',
                Rule::exists('properties', 'uuid')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_business_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],

            'units' => ['required', 'array', 'min:1'],
            'units.*.name' => [
                'required',
                'distinct',
                'string',
                'max:255',
                Rule::unique('property_units', 'name')
                    ->where(fn ($query) => $query->where('property_id', $propertyId)->whereNull('deleted_at')),
            ],
            'units.*.capacity' => ['required', 'integer', 'min:1'],
            'units.*.rent_price' => ['required', 'numeric', 'min:0'],
            'units.*.status' => ['nullable', 'string', Rule::enum(PropertyUnitStatus::class)],
            'units.*.amenity_uuids' => ['sometimes', 'array'],
            'units.*.amenity_uuids.*' => [
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
            'property_uuid.required' => 'A property must be selected for this unit.',
            'property_uuid.exists' => 'The selected property could not be found.',
            'units.required' => 'Please provide at least one unit.',
            'units.*.name.required' => 'Unit name is required.',
            'units.*.name.unique' => 'This property already has a unit with that name.',
            'units.*.name.distinct' => 'Unit names must be unique within this request.',
            'units.*.capacity.min' => 'A unit must be able to house at least one tenant.',
            'units.*.rent_price.min' => 'Rent price cannot be negative.',
            'units.*.amenity_uuids.*.exists' => 'One or more selected amenities are not available for a unit (they may be property-only).',
            'units.*.amenity_uuids.*.distinct' => 'Duplicate amenities are not allowed.',
        ];
    }
}
