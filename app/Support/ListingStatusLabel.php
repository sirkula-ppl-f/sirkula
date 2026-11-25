<?php

namespace App\Support;

/**
 * Label Bahasa Indonesia untuk status listing di halaman admin moderasi (PBI-06).
 *
 * Nilai yang belum punya label (misalnya status baru dari PBI lain atau 'PENDING' dari factory)
 * ditampilkan apa adanya, tanpa error.
 */
final class ListingStatusLabel
{
    /**
     * listings.review_status (verifikasi hub).
     */
    public static function review(?string $value): string
    {
        return self::lookup('review_status', $value);
    }

    /**
     * listings.publish_status (publikasi penjual).
     */
    public static function publish(?string $value): string
    {
        return self::lookup('publish_status', $value);
    }

    /**
     * listings.sale_unit, misalnya "per barang".
     */
    public static function saleUnit(?string $value): string
    {
        return self::lookup('sale_unit', $value);
    }

    private static function lookup(string $group, ?string $value): string
    {
        if ($value === null || $value === '') {
            return __('listing-moderation.admin.none');
        }

        $labels = __('listing-moderation.'.$group);

        return is_array($labels) && isset($labels[$value]) && is_string($labels[$value])
            ? $labels[$value]
            : $value;
    }
}
