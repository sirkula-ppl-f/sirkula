<?php

namespace App\Models;

use App\Enums\ListingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tipe listing yang berlaku untuk satu kategori.
 *
 * Tabel ini tidak punya kolom id, jadi barisnya dikelola lewat relasi
 * Category::categoryListingTypes() (create/delete via query), bukan lewat instance.
 */
#[Table(name: 'category_listing_types', incrementing: false, timestamps: false)]
#[Fillable(['listing_type'])]
class CategoryListingType extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listing_type' => ListingType::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
