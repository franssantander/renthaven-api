<?php

namespace App\Modules\Property\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
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
        $property = $this->route('property');
        return [
            'name' => [
                'sometimes',
                'required',
                'min:3',
                Rule::unique('properties')->ignore($property->id)
            ],
            'type' => ['sometimes', 'required'],
            'description' => ['sometimes', 'min:10'],
            'address_line_1' => ['sometimes', 'required', 'min:5'],
            'address_line_2' => ['sometimes', 'min:5'],
            'city' => ['sometimes', 'required'],
            'state' => ['sometimes', 'required'],
            'zip_code' => ['sometimes', 'required'],
            'number_of_rooms' => ['sometimes', 'integer'],
            'pax' => ['sometimes', 'integer'],
            'number_of_bathrooms' => ['sometimes', 'integer'],
            'area_sq_ft' => ['sometimes', 'integer'],
            'monthly_rent_price' => ['sometimes', 'required', 'integer'],
            'security_deposit' => ['sometimes', 'required', 'integer'],
            'is_available' => ['sometimes', 'boolean'],
            'has_parking' => ['sometimes', 'boolean'],
            'allows_pets' => ['sometimes', 'boolean'],
            'contact_email' => ['sometimes', 'email', 'exists:users,email'],
            'phone_number' => ['sometimes', 'exists:users,phone_number'],
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