<?php

namespace App\Modules\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateUserRequest extends FormRequest
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
            'first_name' => ['required'],
            'last_name' => ['required'],
            'username' => ['required', 'unique:users,username'],
            'password' => ['required', 'min:8'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['required', 'integer', 'unique:users,phone_number'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date'],
            'role_id' => ['required', 'exists:roles,id']
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