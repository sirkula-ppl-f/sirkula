<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubManager extends Model
{
    protected $fillable = [
        'hub_id',
        'user_id',
        'assignment_status',
        'assigned_at',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('assignment_status', 'ACTIVE');
    }
}
