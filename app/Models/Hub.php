<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hub extends Model
{
    protected $fillable = [
        'name',
        'type',
        'region_id',
        'public_address',
        'description',
        'status',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(HubManager::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}