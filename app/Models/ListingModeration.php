<?php

namespace App\Models;

use App\Enums\ListingModerationAction;
use App\Exceptions\ListingModerationImmutableException;
use Database\Factories\ListingModerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu aksi moderasi listing oleh admin (PBI-06, FR-020/FR-021).
 *
 * Append-only: baris yang sudah tercatat tidak bisa diubah atau dihapus lewat model
 * (ListingModerationImmutableException). Update/delete massal lewat query builder
 * (ListingModeration::where()->update(), DB::table()) tidak memicu event model dan tidak terjaga.
 * Hanya ListingModerationService yang boleh menulis ke tabel ini.
 *
 * @property int $id
 * @property int $listing_id
 * @property ListingModerationAction $action
 * @property string $reason
 * @property int $admin_id
 * @property string $request_id
 * @property ?string $review_status_snapshot
 * @property ?string $publish_status_snapshot
 * @property Carbon $created_at disimpan dalam UTC
 */
#[Fillable(['listing_id', 'action', 'reason', 'admin_id', 'request_id', 'review_status_snapshot', 'publish_status_snapshot'])]
class ListingModeration extends Model
{
    /** @use HasFactory<ListingModerationFactory> */
    use HasFactory;

    /**
     * Tidak ada updated_at karena riwayat tidak pernah diubah.
     */
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw ListingModerationImmutableException::update());
        static::deleting(fn () => throw ListingModerationImmutableException::delete());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listing_id' => 'integer',
            'admin_id' => 'integer',
            'action' => ListingModerationAction::class,
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Admin yang melakukan aksi.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
