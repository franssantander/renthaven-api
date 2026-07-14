<?php

namespace App\Http\Requests\PropertyUnit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;

class UpdatePropertyUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $unit = $this->route('property_unit');

        $response = Gate::inspect('update', $unit);

        if ($response->denied()) {
            throw new HttpResponseException(response()->json([
                'data'    => null,
                'status'  => 403,
                'message' => $response->message()
            ], 403));
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
        ];
    }
}