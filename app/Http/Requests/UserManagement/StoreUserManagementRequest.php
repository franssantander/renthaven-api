<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserManagementRequest extends FormRequest
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
        $rules = [
            'role_uuid' => ['required', 'uuid', 'exists:roles,uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ];

        // Super admins must specify which business the user belongs to
        if (auth()->user()->role->slug === 'super_admin') {
            $rules['tenant_business_uuid'] = ['required', 'uuid', 'exists:tenant_businesses,uuid'];
        }

        return $rules;
    }
}