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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $response = Gate::inspect('create', PropertyUnit::class);

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
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_business_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],
            'name'   => ['required', 'unique:property_units,name', 'string', 'max:255'],
            'capacity'    => ['required', 'integer', 'min:0'],
            'rent_price' => ['required', 'numeric', 'min:0'],
            'status'     => ['nullable', 'string', Rule::enum(PropertyUnitStatus::class)],
        ];
    }
}