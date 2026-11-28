<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * An admin can edit everything a member can, and also block / unblock and change the plan.
 */
class AdminProfileUpdateRequest extends ProfileUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'is_active' => ['sometimes', 'boolean'],
            'plan' => ['sometimes', Rule::in(['free', 'premium'])],
            'plan_activated_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }
}
