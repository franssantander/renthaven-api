<?php

namespace App\Http\Requests\PropertyUnit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderPropertyAttachmentsRequest extends FormRequest
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
            'attachment_uuids' => ['required', 'array'],
            'attachment_uuids.*' => [
                'uuid',
                'distinct',
                Rule::exists('property_attachments', 'uuid')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'attachment_uuids.required' => 'Please provide the new photo order.',
            'attachment_uuids.*.exists' => 'One or more photos could not be found.',
            'attachment_uuids.*.distinct' => 'Duplicate photos are not allowed.',
        ];
    }
}
