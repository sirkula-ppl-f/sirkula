<?php

namespace App\Models;

use App\Enums\ListingModerationStatus;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const REVIEW_DRAFT = 'DRAFT';

    public const REVIEW_PENDING = 'PENDING_REVIEW';

    public const REVIEW_APPROVED = 'APPROVED';

    public const PUBLISH_ACTIVE = 'ACTIVE';

    protected $guarded = [];

    /**
     * Nilai default model, disamakan dengan default kolom di database (PBI-06), supaya listing
     * yang baru dibuat langsung punya status moderasi tanpa perlu dimuat ulang.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'moderation_status' => 'VISIBLE',
    ];

    /**
     * moderation_status hanya boleh diubah ListingModerationService (PBI-06).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moderation_status' => ListingModerationStatus::class,
        ];
    }

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
     * Relasi ke jenis material (khusus listing material daur ulang).
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Relasi ke hub komunitas verifikator.
     *
     * @return BelongsTo<Hub, $this>
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }

    /**
     * Relasi ke wilayah listing.
     *
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Aturan listing yang boleh ditampilkan:
     * tidak dinonaktifkan admin (PBI-06), disetujui, aktif, stok tersedia, penjual aktif,
     * dan hub aktif (bila terhubung ke hub).
     */
    public function scopePurchasable(Builder $query): void
    {
        $query->where('moderation_status', ListingModerationStatus::Visible->value)
            ->where('review_status', self::REVIEW_APPROVED)
            ->where('publish_status', self::PUBLISH_ACTIVE)
            ->where('stock', '>', 0)
            ->whereHas('seller', fn (Builder $seller) => $seller->where('status', 'ACTIVE'))
            ->where(function (Builder $q) {
                $q->whereNull('hub_id')
                    ->orWhereHas('hub', fn (Builder $hub) => $hub->where('status', 'ACTIVE'));
            });
    }

    public function isPurchasable(): bool
    {
        return $this->moderation_status === ListingModerationStatus::Visible
            && $this->review_status === self::REVIEW_APPROVED
            && $this->publish_status === self::PUBLISH_ACTIVE
            && $this->stock > 0
            && $this->seller?->isActive() === true
            && ($this->hub_id === null || $this->hub?->status === 'ACTIVE');
    }

    /** @return HasMany<ListingImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class);
    }

    /**
     * Riwayat moderasi admin (PBI-06), terbaru di atas.
     *
     * @return HasMany<ListingModeration, $this>
     */
    public function moderations(): HasMany
    {
        return $this->hasMany(ListingModeration::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
