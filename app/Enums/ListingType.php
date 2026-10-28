<?php

namespace App\Enums;

/**
 * Tipe listing. Nilainya disimpan di category_listing_types.listing_type dan listings.listing_type.
 */
enum ListingType: string
{
    case Preloved = 'PRELOVED';
    case Recyclable = 'RECYCLABLE';
    case Upcycle = 'UPCYCLE';

    public function label(): string
    {
        return match ($this) {
            self::Preloved => 'Barang Bekas',
            self::Recyclable => 'Material Daur Ulang',
            self::Upcycle => 'Produk Upcycle',
        };
    }
}
