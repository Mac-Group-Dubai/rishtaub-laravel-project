<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full profile, meant for the owner of the profile and admins.
 * Public listings should use a smaller resource that hides phone and email.
 */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
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
            'is_active' => (bool) $this->is_active,
            'plan' => $this->plan,
            'plan_activated_at' => $this->plan_activated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
