<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    use HasUuids;

    protected $table = 'user_blocks';
    const UPDATED_AT = null;

    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'conversation_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'blocked_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
