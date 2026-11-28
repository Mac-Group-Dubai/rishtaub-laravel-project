<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQuotaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'interests_remaining' => (int) $this->interests_remaining,
            'contact_views_remaining' => (int) $this->contact_views_remaining,
            'gallery_uploads_remaining' => (int) $this->gallery_uploads_remaining,
            'profile_views_this_week' => (int) $this->profile_views_this_week,
            'quota_reset_at' => $this->quota_reset_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
