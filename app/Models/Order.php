<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model pesanan untuk pencatatan transaksi dan aktivitas sirkular.
 *
 * STUB sementara untuk mendukung PBI-20 (catatan aktivitas sirkular).
 * Koordinasikan dengan Abdullah (PBI-12/14) dan Bobby (PBI-13/15) saat
 * mengintegrasikan modul pesanan dan pembayaran.
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'order_number',
        'checkout_key',
        'status',
        'total',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Pembeli yang membuat pesanan.
     *
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Penjual pemilik barang pada pesanan.
     *
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Daftar item snapshot di dalam pesanan.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Hanya pesanan yang sudah berstatus PAID (Lunas).
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    /**
     * Pesanan yang melibatkan pengguna tertentu (sebagai pembeli atau penjual).
     */
    public function scopeForParticipant(Builder $query, User|int $user): void
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        $query->where(function (Builder $q) use ($userId) {
            $q->where('buyer_id', $userId)
                ->orWhere('seller_id', $userId);
        });
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }
}
