<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Models\Concerns\HasUsageCheck;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori produk. Data master nasional, tidak per wilayah.
 *
 * Kontrak untuk PBI-07/08 (listing):
 * - Tabel listings wajib memakai kolom category_id (FK ke categories, disarankan
 *   ->constrained()->restrictOnDelete()) dan listing_type (string, nilai App\Enums\ListingType).
 * - Pilihan kategori di form: Category::active()->forListingType($type)->orderBy('name')->get().
 * - Kategori yang sudah dipakai listing tidak bisa dihapus, hanya dinonaktifkan. Listing lama
 *   tetap menunjuk kategori itu, jadi riwayat tidak berubah.
 */
#[Fillable(['name', 'slug', 'description', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUsageCheck;

    /**
     * Nilai default model, disamakan dengan default kolom di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CategoryListingType, $this>
     */
    public function categoryListingTypes(): HasMany
    {
        return $this->hasMany(CategoryListingType::class);
    }

    /**
     * Tipe listing yang berlaku untuk kategori ini, urut sesuai urutan case di enum.
     *
     * @return Collection<int, ListingType>
     */
    public function listingTypes(): Collection
    {
        $types = $this->categoryListingTypes->pluck('listing_type');

        return collect(ListingType::cases())
            ->filter(fn (ListingType $type) => $types->contains($type))
            ->values();
    }

    /**
     * Tipe listing yang sedang dipakai listing dengan kategori ini.
     *
     * Bila listings belum punya kolom listing_type, semua tipe kategori dianggap dipakai
     * selama kategori dipakai (pilihan aman).
     *
     * @return Collection<int, ListingType>
     */
    public function listingTypesInUse(): Collection
    {
        if (! Schema::hasColumn('listings', 'category_id')) {
            return collect();
        }

        if (! Schema::hasColumn('listings', 'listing_type')) {
            return $this->isInUse() ? $this->listingTypes() : collect();
        }

        $values = DB::table('listings')
            ->where('category_id', $this->getKey())
            ->distinct()
            ->pluck('listing_type');

        return collect(ListingType::cases())
            ->filter(fn (ListingType $type) => $values->contains($type->value))
            ->values();
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * Kategori yang berlaku untuk tipe listing tertentu.
     */
    #[Scope]
    protected function forListingType(Builder $query, ListingType $type): void
    {
        $query->whereHas('categoryListingTypes', fn (Builder $types) => $types->where('listing_type', $type->value));
    }

    /**
     * Kategori dianggap dipakai bila ada baris listings dengan category_id ini.
     *
     * @return array<string, string>
     */
    protected static function usageReferences(): array
    {
        return ['listings' => 'category_id'];
    }
}
