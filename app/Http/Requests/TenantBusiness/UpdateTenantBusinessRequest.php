<?php

namespace App\Http\Requests\TenantBusiness;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name'             => ['sometimes', 'required', 'string', 'max:255'],
            'email'            => ['sometimes', 'required', 'email', 'unique:tenant_businesses,email'],
            'phone'            => ['sometimes', 'required', 'string', 'unique:tenant_businesses,phone'],
            'business_address' => ['sometimes', 'nullable', 'string'],
            'contact_person'   => ['sometimes', 'nullable', 'string'],
            'logo_url'   => ['sometimes', 'nullable', 'string'],
            'tin'              => ['sometimes', 'nullable', 'string', 'max:15', 'unique:tenant_businesses,tin'],
            'status'           => ['sometimes', 'nullable', 'string', 'in:active,inactive'],
        ];
    }
}