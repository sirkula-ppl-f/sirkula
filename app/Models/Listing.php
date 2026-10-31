<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model listing untuk katalog dan keranjang.
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const REVIEW_APPROVED = 'APPROVED';

    public const PUBLISH_ACTIVE = 'ACTIVE';

    protected $guarded = [];

    /**
     * Relasi ke penjual.
     *
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke kategori listing.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Aturan listing yang boleh ditampilkan:
     * disetujui, aktif, stok tersedia, dan penjual aktif.
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
