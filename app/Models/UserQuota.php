<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuota extends Model
{
    protected $table = 'user_quota';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'interests_remaining',
        'contact_views_remaining',
        'gallery_uploads_remaining',
        'profile_views_this_week',
        'quota_reset_at',
    ];

    protected function casts(): array
    {
        return [
            'quota_reset_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }
}
