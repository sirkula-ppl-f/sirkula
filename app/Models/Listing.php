<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STUB untuk PBI-11. Diganti oleh model Listing asli dari PBI-07/08.
 * Yang dipakai keranjang: seller(), purchasable(), isPurchasable(), price, stock.
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const REVIEW_APPROVED = 'APPROVED';

    public const PUBLISH_ACTIVE = 'ACTIVE';

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Aturan tayang FR-012: disetujui, aktif, stok > 0, penjual aktif.
     * (Syarat hub aktif ditambahkan setelah PBI-01 selesai.)
     */
    public function scopePurchasable(Builder $query): void
    {
        $query->where('review_status', self::REVIEW_APPROVED)
            ->where('publish_status', self::PUBLISH_ACTIVE)
            ->where('stock', '>', 0)
            ->whereHas('seller', fn (Builder $seller) => $seller->where('status', 'ACTIVE'));
    }

    public function isPurchasable(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED
            && $this->publish_status === self::PUBLISH_ACTIVE
            && $this->stock > 0
            && $this->seller?->isActive() === true;
    }
}
