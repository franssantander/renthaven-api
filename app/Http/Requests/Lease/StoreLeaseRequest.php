<?php

namespace App\Http\Requests\Lease;

use App\Enum\LeaseTermType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the `tenants` input so the endpoint accepts:
     *  - a single uuid string
     *  - a single tenant object (new tenant details)
     *  - an array of either of the above, mixed
     * into a uniform array of tenant objects (each either `{uuid}` or new-tenant fields).
     */
    protected function prepareForValidation(): void
    {
        $tenants = $this->input('tenants');

        if ($tenants === null) {
            return;
        }

        if (is_string($tenants)) {
            $tenants = ['uuid' => $tenants];
        }

        if (is_array($tenants) && !array_is_list($tenants)) {
            $tenants = [$tenants];
        }

        $tenants = array_map(
            fn($tenant) => is_string($tenant) ? ['uuid' => $tenant] : $tenant,
            $tenants
        );

        $this->merge(['tenants' => $tenants]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'property_unit_uuid' => [
                'required',
                'uuid',
                Rule::exists('property_units', 'uuid')->whereNull('deleted_at'),
            ],
            'term_type'  => ['required', Rule::enum(LeaseTermType::class)],
            'start_date' => ['required', 'date'],
            'end_date'   => [
                'nullable', 'date', 'after_or_equal:start_date',
                'required_if:term_type,' . LeaseTermType::FIXED_TERM->value,
                'prohibited_if:term_type,' . LeaseTermType::MONTHLY->value,
            ],

            'tenants'   => ['required', 'array', 'min:1'],

            // existing tenant: identified by an existing user's uuid
            'tenants.*.uuid' => ['nullable', 'string', 'uuid', 'exists:users,uuid'],

            // new tenant: required unless a uuid was given for this entry
            'tenants.*.first_name' => ['required_without:tenants.*.uuid', 'nullable', 'string', 'max:255'],
            'tenants.*.last_name'  => ['required_without:tenants.*.uuid', 'nullable', 'string', 'max:255'],
            'tenants.*.email'      => ['required_without:tenants.*.uuid', 'nullable', 'string', 'email', 'max:255'],
            'tenants.*.phone'      => ['nullable', 'string', 'max:50'],
        ];
    }
}