<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasUuids;

    protected $table = 'faqs';
    const UPDATED_AT = null;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
