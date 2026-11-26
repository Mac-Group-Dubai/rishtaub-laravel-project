<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Same fields and rules as the live Signup form:
 * onBehalf, firstName, lastName, gender, countryCode, phone, dob, email, password, confirm.
 */
class RegisterRequest extends FormRequest
{
    // Country codes offered by the live signup form
    public const COUNTRY_CODES = ['+92', '+971', '+966', '+974', '+965', '+973', '+968', '+91', '+44', '+1'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // The live form trims names and email, and Supabase stores emails in lowercase
        $this->merge([
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'first_name' => is_string($this->first_name) ? trim(strip_tags($this->first_name)) : $this->first_name,
            'last_name' => is_string($this->last_name) ? trim(strip_tags($this->last_name)) : $this->last_name,
        ]);
    }

    public function rules(): array
    {
        return [
            // live: Myself, Friend, Brother, Sister, Son, Daughter (stored as self, friend, ...)
            'onbehalf' => ['nullable', Rule::in(['self', 'friend', 'brother', 'sister', 'son', 'daughter'])],
            'first_name' => ['required', 'string', 'min:1', 'max:50'],
            'last_name' => ['required', 'string', 'min:1', 'max:50'],
            // live form offers only Male and Female
            'gender' => ['required', Rule::in(['male', 'female'])],
            'country_code' => ['required', Rule::in(self::COUNTRY_CODES)],
            'phone' => ['required', 'string', 'min:6', 'max:12'],
            'dob' => ['required', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:profiles,email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name required',
            'last_name.required' => 'Last name required',
            'country_code.required' => 'Select a country code',
            'phone.min' => 'Enter a valid phone number',
            'phone.max' => 'Enter a valid phone number',
            'dob.required' => 'Date of birth required',
            'email.email' => 'Invalid email',
            'email.unique' => 'This email is already registered. Try logging in.',
            'password.min' => 'Minimum 8 characters',
            'password.confirmed' => 'Passwords do not match',
        ];
    }
}
