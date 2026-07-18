<?php

namespace App\Http\Requests\Ledger;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'proof'     => ['required', 'image', 'max:5120'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.max'  => 'The payment reference may not be longer than 255 characters.',
            'notes.max'      => 'Your note may not be longer than 1000 characters.',
            'proof.required' => 'Please attach a screenshot or photo of your payment as proof.',
            'proof.image'    => 'The payment proof must be an image.',
            'proof.max'      => 'The payment proof image may not be larger than 5MB.',
        ];
    }
}
