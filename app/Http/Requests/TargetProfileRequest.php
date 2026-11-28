<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Body of "add to shortlist" and "ignore profile": { "target_id": "<profile id>" }
class TargetProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => [
                'required', 'uuid',
                Rule::exists('profiles', 'id')->where('is_active', true),
                Rule::notIn([$this->user()->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.exists' => 'That profile was not found.',
            'target_id.not_in' => 'You cannot choose your own profile.',
        ];
    }
}
