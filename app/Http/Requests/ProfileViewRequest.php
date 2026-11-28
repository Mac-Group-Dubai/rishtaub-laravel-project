<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Body of "log a profile view": { "viewed_id": "<profile id>" }
class ProfileViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'viewed_id' => ['required', 'uuid', Rule::exists('profiles', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return ['viewed_id.exists' => 'That profile was not found.'];
    }
}
