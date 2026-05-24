<?php

namespace App\Modules\Property\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreatePropertyRequest extends FormRequest
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
            'name' => ['required', 'min:3', Rule::unique('properties', 'name')->where('tenant_id', auth()->user()->tenant_id)],
            'type' => ['required'],
            'description' => ['required', 'min:10'],
            'address_line_1' => ['required', 'min:5'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['required'],
            'state' => ['required'],
            'zip_code' => ['required'],
            'number_of_rooms' => ['nullable', 'integer'],
            'number_of_bathrooms' => ['nullable', 'integer'],
            'pax' => ['required', 'integer'],
            'is_shared' => ['required', 'boolean'],
            'total_units' => ['required', 'integer', 'min:1'],
            'area_sq_ft' => ['required', 'integer'],
            'monthly_rent_price' => ['required', 'numeric', 'min:0'],
            'security_deposit' => ['required', 'numeric', 'min:0'],
            'is_available' => ['boolean'],
            'has_parking' => ['required', 'boolean'],
            'allows_pets' => ['required', 'boolean'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['exists:amenities,id'],
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