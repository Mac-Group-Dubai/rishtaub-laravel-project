<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What one member sees of another member. Same fields as the live profile row,
 * except email and phone, which the live pages never show for other members.
 */
class PublicProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'dob' => $this->dob?->toDateString(),
            'onbehalf' => $this->onbehalf,
            'religion' => $this->religion,
            'caste' => $this->caste,
            'marital_status' => $this->marital_status,
            'education' => $this->education,
            'profession' => $this->profession,
            'city' => $this->city,
            'country' => $this->country,
            'about' => $this->about,
            'mother_tongue' => $this->mother_tongue,
            'height' => $this->height,
            'children' => $this->children,
            'salary' => $this->salary,
            'photo' => $this->photo,
            'plan' => $this->plan,
            'created_at' => $this->created_at,
        ];
    }
}
