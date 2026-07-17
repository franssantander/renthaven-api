<?php

namespace App\Http\Requests\Property;

use App\Enum\PropertyType;
use App\Enum\Role;
use App\Models\Property;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
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
        return [
            'tenant_business_uuid' => ['required', 'uuid', 'exists:tenant_businesses,uuid'],
            'name'                 => ['required', 'unique:properties,name', 'string', 'max:255'],
            'address'              => ['nullable', 'string', 'max:1000'],
            'type'                 => ['required', Rule::enum(PropertyType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role?->slug !== Role::SUPER_ADMIN->value) {
            $this->merge(['tenant_business_uuid' => $this->user()?->tenantBusiness?->uuid]);
        }
    }
}