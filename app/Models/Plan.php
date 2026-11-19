<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'plans';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'price',
        'duration',
        'tagline',
        'highlight',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'highlight' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'plan_id')->orderBy('sort_order');
    }
}
