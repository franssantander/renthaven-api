<?php

namespace App\Modules\RenterManagement\Http\Requests;

use App\Modules\RenterManagement\Models\Lease;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRenterRequest extends FormRequest
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
        $lease = $this->route('lease');

        $userId = $lease instanceof Lease
            ? $lease->renter_user_id
            : null;

        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'username' => [
                'sometimes',
                'string',
                Rule::unique('renter_users')->ignore($userId)
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('renter_users')->ignore($userId)
            ],
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8',
            'phone_number' => 'sometimes|string',
            'age' => 'sometimes|integer',
            'unit_number' => 'nullable|string|max:255',
            'lease_type' => 'sometimes|string|in:fixed,monthly',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|required_if:lease_type,fixed|date',
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