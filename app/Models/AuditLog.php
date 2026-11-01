<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Karena tabel audit_logs hanya punya created_at (tanpa updated_at),
     * kita matikan timestamps bawaan Laravel.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'result',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke model User (siapa admin/user yang melakukan aksi).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
