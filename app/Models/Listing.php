<?php

namespace App\Models;

use App\Enums\ListingType;
use Illuminate\Database\Eloquent\Model; // Pastikan memanggil Enum buatan temanmu

class Listing extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'hub_id', 'region_id', 'listing_type', // Ubah type jadi listing_type
        'title', 'description', 'price', 'stock', 'condition', 'status',
        'weight_per_lot', 'material_id', 'storefront_id',
    ];

    protected $casts = [
        'listing_type' => ListingType::class, // Cast kolom ke Enum
    ];

    // ... relasi lainnya ...
}
