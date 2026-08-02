<?php

namespace App\Http\Requests\Amenity;

use App\Enum\AmenityCategory;
use App\Enum\AmenityScope;
use App\Enum\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAmenityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role?->slug, [
            Role::SUPER_ADMIN->value,
            Role::ADMIN->value,
        ], true);
    }

    /**
     * Normalize a single amenity body into an `amenities` array of one, so the
     * endpoint accepts creating one or several custom amenity tags at once.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('amenities') && $this->has('name')) {
            $this->merge([
                'amenities' => [
                    [
                        'name' => $this->input('name'),
                        'slug' => $this->input('slug'),
                        'category' => $this->input('category'),
                        'scope' => $this->input('scope'),
                        'icon' => $this->input('icon'),
                    ],
                ],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amenities' => ['required', 'array', 'min:1'],
            'amenities.*.name' => ['required', 'string', 'max:255'],
            'amenities.*.slug' => ['nullable', 'string', 'max:255', 'distinct', 'unique:amenities,slug'],
            'amenities.*.category' => ['nullable', 'string', Rule::enum(AmenityCategory::class)],
            'amenities.*.scope' => ['nullable', 'string', Rule::enum(AmenityScope::class)],
            'amenities.*.icon' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amenities.required' => 'Please provide at least one amenity.',
            'amenities.*.name.required' => 'Amenity name is required.',
            'amenities.*.slug.unique' => 'This amenity slug is already in use.',
        ];
    }
}
