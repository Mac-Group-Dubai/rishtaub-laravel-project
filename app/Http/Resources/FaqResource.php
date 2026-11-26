<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            $this->mergeWhen($request->routeIs('admin.*'), [
                'sort_order' => $this->sort_order,
                'active' => (bool) $this->active,
                'created_at' => $this->created_at,
            ]),
        ];
    }
}
