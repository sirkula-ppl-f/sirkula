<?php

namespace App\Enums;

/**
 * Status moderasi admin, disimpan di listings.moderation_status (PBI-06).
 *
 * Terpisah dari review_status (verifikasi hub) dan publish_status (publikasi penjual).
 * VISIBLE berarti admin tidak membatasi listing ini; listing baru tampil di katalog bila semua
 * syarat Listing::scopePurchasable() lain juga terpenuhi.
 */
enum ListingModerationStatus: string
{
    case Visible = 'VISIBLE';
    case Hidden = 'HIDDEN';

    public function label(): string
    {
        return __('listing-moderation.moderation_status.'.$this->value);
    }
}
