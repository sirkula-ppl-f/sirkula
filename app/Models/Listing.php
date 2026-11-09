<?php

namespace App\Models;

use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model listing untuk katalog dan keranjang.
 *
 * STUB sementara untuk mendukung PBI-11 (keranjang) dan PBI-10 (katalog).
 * Model serta migrasi Listing asli dari PBI-07/08 dapat menggantikan stub ini.
 * Koordinasikan perubahan dengan Bobby (keranjang) dan Adelia (listing)
 * agar relasi dan kebutuhan modul yang sudah berjalan tidak hilang.
 */
class Listing extends Model
{
    use HasFactory;

    public const REVIEW_APPROVED = 'APPROVED';

    public const PUBLISH_ACTIVE = 'ACTIVE';

    protected $guarded = [];

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

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

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }
}
