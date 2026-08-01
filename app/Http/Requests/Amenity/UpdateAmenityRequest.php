<?php

namespace App\Http\Requests\Amenity;

use App\Enum\AmenityCategory;
use App\Enum\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $amenityId = $this->route('amenity')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('amenities', 'slug')->ignore($amenityId)],
            'category' => ['nullable', 'string', Rule::enum(AmenityCategory::class)],
            'icon' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Amenity name is required.',
            'slug.unique' => 'This amenity slug is already in use.',
        ];
    }
}
