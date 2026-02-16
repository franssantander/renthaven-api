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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid', 'exists:leases,uuid'],
            'property_id' => [
                'required',
                'uuid',
                'exists:properties,uuid',
                function ($attribute, $value, $fail) {
                    $property = Property::where('uuid', $value)
                        ->withCount(['activeLeases'])
                        ->first();

                    if (!$property) {
                        return $fail('Target property not found.');
                    }
                    $movingCount = count($this->ids ?? []);
                    $availableSlots = $property->pax - $property->active_leases_count;

                    if ($movingCount > $availableSlots) {
                        return $fail("Not enough space. '{$property->name}' only has {$availableSlots} slot(s) left, but you are moving {$movingCount} tenant(s).");
                    }
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