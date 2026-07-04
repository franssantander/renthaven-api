<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantBusinessRequest extends FormRequest
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
            'plan_id'          => ['required', 'exists:plans,id'],
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:tenant_businesses,email'],
            'phone'            => ['required', 'string', 'unique:tenant_businesses,phone'],
            'business_address' => ['nullable', 'string'],
            'contact_person'   => ['nullable', 'string'],
            'tin'              => ['nullable', 'string', 'max:15', 'unique:tenant_businesses,tin'],
            'status'           => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
