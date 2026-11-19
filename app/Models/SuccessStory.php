<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SuccessStory extends Model
{
    use HasUuids;

    protected $table = 'success_stories';
    const UPDATED_AT = null;

    protected $fillable = [
        'couple',
        'location',
        'date',
        'quote',
        'image',
        'published',
        'sort_order',
        'visible',
        'story_date',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'created_at' => 'datetime',
            'visible' => 'boolean',
        ];
    }
}
