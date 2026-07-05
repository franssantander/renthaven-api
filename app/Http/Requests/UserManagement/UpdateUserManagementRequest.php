<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Logging\OpenTestReporting\Status;

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
            'full_name' => ['sometimes', 'string', 'max:255'],
            'username'  => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email'     => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => ['nullable', 'string', 'max:50'],
            'status'    => ['sometimes', Rule::enum(Status::class)],
            'password'  => ['nullable', 'confirmed', Password::defaults()],
            'role_id'   => ['sometimes', 'exists:roles,id'],
        ];

        if (auth()->user()->role->slug === 'super_admin') {
            $rules['tenant_business_id'] = ['sometimes', 'exists:tenant_businesses,id'];
        }

        return $rules;
    }
}