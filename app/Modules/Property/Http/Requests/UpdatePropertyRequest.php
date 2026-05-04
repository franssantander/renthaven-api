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
        $propertyId = is_object($property) ? $property->id : $property;
        
        return [
            'name' => [
                'sometimes',
                'required',
                'min:3',
                Rule::unique('properties', 'name')
                    ->ignore($propertyId)
                    ->where('tenant_id', auth()->user()->tenant_id)
            ],
            'type' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'required', 'min:10'],
            'address_line_1' => ['sometimes', 'required', 'min:5'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['sometimes', 'required'],
            'state' => ['sometimes', 'required'],
            'zip_code' => ['sometimes', 'required'],

            'number_of_rooms' => ['sometimes', 'integer', 'min:0'],
            'pax' => ['sometimes', 'integer', 'min:1'],
            'number_of_bathrooms' => ['sometimes', 'numeric', 'min:0'],
            'area_sq_ft' => ['sometimes', 'integer', 'min:1'],
            'monthly_rent_price' => ['sometimes', 'required', 'numeric'],
            'security_deposit' => ['sometimes', 'required', 'numeric'],

            'is_available' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'has_parking' => ['sometimes', 'boolean'],
            'allows_pets' => ['sometimes', 'boolean'],

            'contact_email' => ['sometimes', 'required', 'email'],
            'contact_phone' => ['sometimes', 'required'],
            'amenities' => ['sometimes', 'array'],
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