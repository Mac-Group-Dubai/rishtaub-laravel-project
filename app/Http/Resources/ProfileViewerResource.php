<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// A profile row of someone who viewed me, plus when they did
class ProfileViewerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(
            (new PublicProfileResource($this->viewer))->toArray($request),
            ['viewed_at' => $this->viewed_at]
        );
    }
}
