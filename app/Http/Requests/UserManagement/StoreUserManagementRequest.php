<?php

namespace App\Http\Requests\UserManagement;

use App\Enum\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'role_uuid' => [
                'required',
                'uuid',
                Rule::exists('roles', 'uuid')->where(function ($query) {
                    if (auth()->user()?->role?->slug !== Role::SUPER_ADMIN->value) {
                        $query->where('slug', '!=', Role::SUPER_ADMIN->value);
                    }
                }),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Super admins must specify which business the user belongs to
        if (auth()->user()->role->slug === 'super_admin') {
            $rules['tenant_business_uuid'] = ['required', 'uuid', 'exists:tenant_businesses,uuid'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'role_uuid.required' => 'Please select a role for this user.',
            'role_uuid.exists' => 'The selected role is invalid or not permitted.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'tenant_business_uuid.required' => 'Please select which business this user belongs to.',
            'tenant_business_uuid.exists' => 'The selected business could not be found.',
        ];
    }
}
