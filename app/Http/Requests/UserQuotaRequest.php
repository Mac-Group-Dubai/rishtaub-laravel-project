<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Admin only: change a member's remaining interests, contact views, gallery uploads and reset date
class UserQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    public function rules(): array
    {
        return [
            'interests_remaining' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'contact_views_remaining' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'gallery_uploads_remaining' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'profile_views_this_week' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'quota_reset_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
