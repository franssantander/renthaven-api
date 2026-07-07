<?php

namespace App\Http\Requests\Permission;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncUserPermissionRequest extends FormRequest
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
            'user_id' => ['required', 'uuid', 'exists:users,uuid'],
            'permissions' => ['required', 'array'],
            'permissions.*.module_uuid' => ['required', 'uuid', 'exists:permission_modules,uuid'],
            'permissions.*.action_uuid' => ['required', 'array'],
            'permissions.*.action_uuid.*' => ['required', 'uuid', 'exists:permission_actions,uuid'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * (Optional: Makes error messages look much cleaner for nested arrays)
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user UUID',
            'permissions.*.module_uuid' => 'module UUID',
            'permissions.*.action_uuids' => 'action UUIDs list',
            'permissions.*.action_uuids.*' => 'action UUID',
        ];
    }
}