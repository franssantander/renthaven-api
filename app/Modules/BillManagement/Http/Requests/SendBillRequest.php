<?php

namespace App\Modules\BillManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendBillRequest extends FormRequest
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
            'lease_id' => ['required', 'exists:leases,uuid'],
            'type' => ['required'],
            'status' => ['required', 'in:paid,unpaid,overdue'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255']
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