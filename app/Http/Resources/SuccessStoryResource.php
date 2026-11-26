<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Same shape as the live site's stories: id, couple, date, location, quote, image
 */
class SuccessStoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'couple' => $this->couple,
            'date' => $this->date,
            'story_date' => $this->story_date,
            'location' => $this->location,
            'quote' => $this->quote,
            'image' => $this->image,
            $this->mergeWhen($request->routeIs('admin.*'), [
                'published' => (bool) $this->published,
                'visible' => (bool) $this->visible,
                'sort_order' => $this->sort_order,
                'created_at' => $this->created_at,
            ]),
        ];
    }
}
