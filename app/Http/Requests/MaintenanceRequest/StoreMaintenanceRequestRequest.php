<?php

namespace App\Http\Requests\MaintenanceRequest;

use App\Enum\MaintenanceCategory;
use App\Enum\MaintenancePriority;
use App\Enum\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
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
        $isTenant = $this->user()?->role?->slug === Role::TENANT->value;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', Rule::enum(MaintenanceCategory::class)],
            'priority' => ['nullable', 'string', Rule::enum(MaintenancePriority::class)],
            'lease_uuid' => [$isTenant ? 'prohibited' : 'required', 'uuid', 'exists:leases,uuid'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for this request.',
            'description.required' => 'Please describe the issue.',
            'category.required' => 'Please select a category.',
            'lease_uuid.required' => 'Please select which lease this request is for.',
            'lease_uuid.prohibited' => 'Tenants cannot specify a lease directly; it is inferred from your account.',
            'lease_uuid.exists' => 'The selected lease could not be found.',
        ];
    }
}
