<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    use HasUuids;

    protected $table = 'blogs';

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'cover',
        'category',
        'author_name',
        'author_avatar',
        'read_time',
        'published',
        'views',
        'published_at',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(BlogAuthor::class, 'author_id');
    }
}
