<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// The live Pricing page lets a member switch between the free and premium plan
class PlanChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(['free', 'premium'])],
        ];
    }
}
