<?php

namespace App\Modules\RoomManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteTenantFromPropertyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required_without:clear_all', 'array'],
            'ids.*' => ['uuid'],
            'property_id' => ['required_if:clear_all,true', 'uuid'],
            'clear_all' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required_without' => 'Either select specific tenants (ids) or set clear_all to true.',
            'property_id.required_if' => 'The property_id is required when clearing all tenants.',
            'ids.array' => 'The ids field must be an array of UUIDs.',
        ];
    }

    /**
     * Optional: Override failed validation to return JSON immediately.
     * (Useful if your API middleware doesn't automatically handle this)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Please check the highlighted fields and try again.',
            'errors' => $validator->errors(),
            'status' => 422
        ], 422));
    }
}