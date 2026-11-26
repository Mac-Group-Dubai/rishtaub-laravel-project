<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Same shape as the live site's plans: id, name, price, duration, tagline, highlight, features[{label, included}]
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admin = $request->routeIs('admin.*');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'duration' => $this->duration,
            'tagline' => $this->tagline,
            'highlight' => (bool) $this->highlight,
            'features' => $this->whenLoaded('features', fn () => $this->features->map(fn ($f) => array_merge(
                ['label' => $f->label, 'included' => (bool) $f->included],
                $admin ? ['id' => $f->id, 'sort_order' => $f->sort_order] : []
            ))->values()),
            $this->mergeWhen($admin, [
                'active' => (bool) $this->active,
                'sort_order' => $this->sort_order,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]),
        ];
    }
}
