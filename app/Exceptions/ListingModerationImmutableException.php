<?php

namespace App\Exceptions;

use LogicException;

/**
 * Riwayat moderasi listing bersifat append-only: baris yang sudah tercatat tidak boleh diubah atau dihapus.
 * Perubahan status berikutnya dicatat sebagai baris baru.
 */
class ListingModerationImmutableException extends LogicException
{
    public static function update(): self
    {
        return new self(__('listing-moderation.immutable.update'));
    }

    public static function delete(): self
    {
        return new self(__('listing-moderation.immutable.delete'));
    }
}
