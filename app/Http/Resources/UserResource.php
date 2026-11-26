<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'profile' => $this->whenLoaded('profile', fn () => $this->profile ? new ProfileResource($this->profile) : null),
            'created_at' => $this->created_at,
        ];
    }
}
