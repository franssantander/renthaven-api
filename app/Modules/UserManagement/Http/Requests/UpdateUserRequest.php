<?php

namespace App\Modules\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
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
            'first_name' => ['sometimes'],
            'last_name' => ['sometimes'],
            'username' => ['sometimes', 'unique:users,username'],
            'password' => ['sometimes', 'min:8'],
            'email' => ['sometimes', 'email', 'unique:users,email'],
            'phone_number' => ['sometimes', 'unique:users,phone_number'],
            'gender' => ['sometimes', 'in:male,female'],
            'birth_date' => ['sometimes', 'date'],
            'role_id' => ['sometimes', 'exists:roles,id']
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