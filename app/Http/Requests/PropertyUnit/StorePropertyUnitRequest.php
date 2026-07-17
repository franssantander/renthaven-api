<?php

namespace App\Http\Requests\PropertyUnit;

use App\Enum\PropertyUnitStatus;
use App\Models\PropertyUnit;
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
        if (!$this->has('units') && $this->has('name')) {
            $this->merge([
                'units' => [
                    [
                        'name'       => $this->input('name'),
                        'capacity'   => $this->input('capacity'),
                        'rent_price' => $this->input('rent_price'),
                        'status'     => $this->input('status'),
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
                'data'    => null,
                'status'  => 403,
                'message' => $response->message()
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

        return [
            'property_uuid' => [
                'required',
                'uuid',
                Rule::exists('properties', 'uuid')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_business_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],

            'units'                => ['required', 'array', 'min:1'],
            'units.*.name'         => ['required', 'distinct', 'string', 'max:255', 'unique:property_units,name'],
            'units.*.capacity'     => ['required', 'integer', 'min:0'],
            'units.*.rent_price'   => ['required', 'numeric', 'min:0'],
            'units.*.status'       => ['nullable', 'string', Rule::enum(PropertyUnitStatus::class)],
        ];
    }
}