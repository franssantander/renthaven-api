<?php

namespace App\Http\Requests\Ledger;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be greater than zero.',
        ];
    }

    /**
     * Reject an amount that exceeds the entry's outstanding balance, rather
     * than letting it be silently clamped and the excess discarded.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $amount = $this->input('amount');
            $ledgerEntry = $this->route('ledgerEntry');

            if ($amount !== null && $ledgerEntry && (float) $amount > (float) $ledgerEntry->balance) {
                $validator->errors()->add('amount', 'The amount cannot exceed the outstanding balance of '.number_format((float) $ledgerEntry->balance, 2).'.');
            }
        });
    }
}
