<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    use HasUuids;

    protected $table = 'profile_views';
    public $timestamps = false;

    protected $fillable = [
        'viewer_id',
        'viewed_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'viewer_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'viewed_id');
    }
}
