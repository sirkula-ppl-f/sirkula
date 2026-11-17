<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Header pesanan. Status dipegang dua pihak:
 * - Checkout (PBI-12): PENDING_PAYMENT, EXPIRED, CANCELLED
 * - Pembayaran (PBI-13): PAID, PAYMENT_REVIEW
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';

    public const STATUS_PAYMENT_REVIEW = 'PAYMENT_REVIEW';

    public const STATUS_PAID = 'PAID';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_EXPIRED = 'EXPIRED';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /** Pesanan masih menunggu pembayaran dan belum melewati batas waktu. */
    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
