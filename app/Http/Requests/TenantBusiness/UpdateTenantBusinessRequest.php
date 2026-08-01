<?php

namespace App\Http\Requests\TenantBusiness;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantBusinessRequest extends FormRequest
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
        $tenantBusinessId = $this->route('tenantBusiness')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('tenant_businesses', 'email')->ignore($tenantBusinessId)],
            'phone' => ['sometimes', 'required', 'string', Rule::unique('tenant_businesses', 'phone')->ignore($tenantBusinessId)],
            'business_address' => ['sometimes', 'nullable', 'string'],
            'contact_person' => ['sometimes', 'nullable', 'string'],
            'logo_url' => ['sometimes', 'nullable', 'string'],
            'tin' => ['sometimes', 'nullable', 'string', 'max:15', Rule::unique('tenant_businesses', 'tin')->ignore($tenantBusinessId)],
            'status' => ['sometimes', 'nullable', 'string', 'in:active,inactive'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Business name is required.',
            'email.required' => 'Business email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'Business phone is required.',
            'phone.unique' => 'This phone number is already registered.',
            'tin.unique' => 'This TIN is already registered.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }
}
