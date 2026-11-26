<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Same shape as the live site's blogs: id, slug, title, category, excerpt, date, readTime, cover, author{name, avatar, title}, content
 */
class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admin = $request->routeIs('admin.*');
        $isList = $request->routeIs('blogs.index', 'admin.blogs.index');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'date' => $this->published_at?->format('F j, Y'),
            'readTime' => $this->read_time,
            'cover' => $this->cover,
            'author' => [
                'name' => $this->author?->name ?? $this->author_name,
                'avatar' => $this->author?->avatar ?? $this->author_avatar,
                'title' => $this->author?->title,
            ],
            'content' => $this->when(! $isList, $this->content), // lists stay light, the full text comes with show
            $this->mergeWhen($admin, [
                'author_id' => $this->author_id,
                'published' => (bool) $this->published,
                'published_at' => $this->published_at,
                'views' => (int) $this->views,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]),
        ];
    }
}
