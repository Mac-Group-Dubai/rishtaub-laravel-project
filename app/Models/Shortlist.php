<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortlist extends Model
{
    use HasUuids;

    protected $table = 'shortlists';
    const UPDATED_AT = null;

    protected $fillable = [
        'owner_id',
        'target_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'owner_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'target_id');
    }
}
