<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields the live MyProfile page saves:
 * first_name, last_name, dob, gender, height, city, country, profession, education, salary,
 * mother_tongue, onbehalf, religion, caste, marital_status, children, phone, about.
 * Anything else in the request is ignored (id, email, is_active, plan can not be changed here).
 */
class ProfileUpdateRequest extends FormRequest
{
    protected const TEXT_FIELDS = [
        'first_name', 'last_name', 'height', 'city', 'country', 'profession', 'education', 'salary',
        'mother_tongue', 'religion', 'caste', 'marital_status', 'children', 'phone', 'about',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (self::TEXT_FIELDS as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim(strip_tags($this->input($field)));
            }
        }
        $this->merge($clean);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'height' => ['sometimes', 'nullable', 'string', 'max:50'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'salary' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mother_tongue' => ['sometimes', 'nullable', 'string', 'max:100'],
            'onbehalf' => ['sometimes', 'nullable', Rule::in(['self', 'friend', 'brother', 'sister', 'son', 'daughter'])],
            'religion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'caste' => ['sometimes', 'nullable', 'string', 'max:100'],
            'marital_status' => ['sometimes', 'nullable', 'string', 'max:100'],
            'children' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'about' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
