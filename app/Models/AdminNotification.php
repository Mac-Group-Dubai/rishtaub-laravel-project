<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    use HasUuids;

    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'body',
        'target',
        'sent_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'sent_by');
    }
}
