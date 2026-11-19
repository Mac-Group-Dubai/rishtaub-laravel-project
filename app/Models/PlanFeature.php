<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasUuids;

    protected $table = 'plan_features';
    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'label',
        'included',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'included' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
