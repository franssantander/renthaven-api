<?php

namespace App\Http\Requests\UserManagement;

use App\Enum\Role;
use App\Enum\Status;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserManagementRequest extends FormRequest
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
        $user = $this->route('user');

        $rules = [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::enum(Status::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_uuid' => [
                'sometimes',
                'uuid',
                Rule::exists('roles', 'uuid')->where(function ($query) {
                    if (auth()->user()?->role?->slug !== Role::SUPER_ADMIN->value) {
                        $query->where('slug', '!=', Role::SUPER_ADMIN->value);
                    }
                }),
            ],
        ];

        if (auth()->user()->role->slug === 'super_admin') {
            $rules['tenant_business_uuid'] = ['sometimes', 'uuid', 'exists:tenant_businesses,uuid'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.string' => 'First name must be a valid string.',
            'last_name.string' => 'Last name must be a valid string.',
            'username.unique' => 'This username is already taken.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'status.enum' => 'Please select a valid account status.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role_uuid.exists' => 'The selected role is invalid or not permitted.',
            'tenant_business_uuid.exists' => 'The selected business could not be found.',
        ];
    }
}
