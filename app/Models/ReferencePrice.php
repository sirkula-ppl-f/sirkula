<?php

namespace App\Models;

use App\Exceptions\ReferencePriceImmutableException;
use App\Services\ReferencePriceService;
use Carbon\CarbonImmutable;
use Database\Factories\ReferencePriceFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * Harga acuan material per provinsi dalam rupiah per kg (PBI-05, FR-008).
 *
 * Append-only: entri yang sudah terbit tidak bisa diubah atau dihapus lewat model
 * (ReferencePriceImmutableException). Koreksi dilakukan dengan menerbitkan entri baru.
 * Update/delete massal lewat query builder (ReferencePrice::where()->update(), DB::table())
 * tidak memicu event model dan tidak terjaga. Modul lain hanya membaca lewat ReferencePriceService.
 *
 * @property int $id
 * @property int $material_id
 * @property int $region_id
 * @property int $price_per_kg
 * @property CarbonImmutable $effective_date tengah malam WIB
 * @property string $source
 * @property int $published_by
 * @property Carbon $created_at disimpan dalam UTC
 */
#[Fillable(['material_id', 'region_id', 'price_per_kg', 'effective_date', 'source', 'published_by'])]
class ReferencePrice extends Model
{
    /** @use HasFactory<ReferencePriceFactory> */
    use HasFactory;

    /**
     * Tidak ada updated_at karena entri tidak pernah diubah.
     */
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw ReferencePriceImmutableException::update());
        static::deleting(fn () => throw ReferencePriceImmutableException::delete());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'material_id' => 'integer',
            'region_id' => 'integer',
            'price_per_kg' => 'integer',
            'published_by' => 'integer',
        ];
    }

    /**
     * Tanggal berlaku disimpan sebagai 'Y-m-d' di semua driver.
     *
     * Cast 'date' bawaan menyimpan 'Y-m-d 00:00:00' di SQLite, sehingga perbandingan
     * effective_date <= '2026-11-12' bernilai salah di tes tetapi benar di MySQL.
     * Nilai dibaca sebagai tengah malam WIB supaya bisa dibandingkan dengan ReferencePriceService::today().
     */
    protected function effectiveDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null
                ? null
                : CarbonImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10), ReferencePriceService::TIMEZONE),
            set: fn (DateTimeInterface|string|null $value) => match (true) {
                $value === null => null,
                $value instanceof DateTimeInterface => $value->format('Y-m-d'),
                default => Date::parse($value)->format('Y-m-d'),
            },
        )->withoutObjectCaching();
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Provinsi tempat harga berlaku.
     *
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Admin yang menerbitkan.
     *
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Entri yang sudah berlaku pada tanggal $date ('Y-m-d', WIB).
     */
    #[Scope]
    protected function effectiveOn(Builder $query, string $date): void
    {
        $query->where($query->qualifyColumn('effective_date'), '<=', $date);
    }

    /**
     * Hanya harga terbaru per pasangan material + provinsi pada tanggal $date ('Y-m-d', WIB):
     * effective_date <= $date, urut effective_date terbaru lalu id terbesar.
     *
     * Memakai subquery berkorelasi (bukan window function) supaya jalan di SQLite, MySQL 5.7/8, dan MariaDB.
     */
    #[Scope]
    protected function latestPerPair(Builder $query, string $date): void
    {
        $query->where($query->qualifyColumn('id'), '=', self::latestIdForPair($query, $date));
    }

    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc($query->qualifyColumn('effective_date'))
            ->orderByDesc($query->qualifyColumn('id'));
    }

    /**
     * Subquery id harga terbaru untuk pasangan material + provinsi pada baris query luar.
     */
    protected static function latestIdForPair(Builder $outer, string $date): QueryBuilder
    {
        $table = $outer->getModel()->getTable();

        return $outer->getQuery()->newQuery()
            ->from($table, 'latest_price')
            ->select('latest_price.id')
            ->whereColumn('latest_price.material_id', $table.'.material_id')
            ->whereColumn('latest_price.region_id', $table.'.region_id')
            ->where('latest_price.effective_date', '<=', $date)
            ->orderByDesc('latest_price.effective_date')
            ->orderByDesc('latest_price.id')
            ->limit(1);
    }
}
