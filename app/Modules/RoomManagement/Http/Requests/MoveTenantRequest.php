<?php

namespace App\Modules\RoomManagement\Http\Requests;

use App\Modules\Property\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MoveTenantRequest extends FormRequest
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
            'property_id' => [
                'required',
                'exists:properties,id',
                function ($attribute, $value, $fail) {
                    $property = Property::withCount(['activeLease'])->find($value);

                    if (!$property)
                        return $fail('Target property not found.');
                    if ($property->active_lease_count >= $property->pax)
                        return $fail("The property '{$property->name}' is already fully occupied (Capacity: {$property->pax}).");
                }
            ]
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