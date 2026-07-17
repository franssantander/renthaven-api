<?php

namespace App\Http\Requests\MaintenanceRequest;

use App\Enum\MaintenanceRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequestStatusRequest extends FormRequest
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
            'status'            => ['required', 'string', Rule::enum(MaintenanceRequestStatus::class)],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'assigned_to_uuid'  => ['nullable', 'uuid', 'exists:users,uuid'],
        ];
    }
}
