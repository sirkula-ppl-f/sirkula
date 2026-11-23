<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Aksi moderasi listing ditolak aturan bisnis (PBI-06): listing draf, atau status sudah sama
 * dengan tujuan (misalnya form lama di tab lain).
 *
 * Controller moderasi menangkap exception ini dan menampilkan flash error. Bila lolos dari
 * pemanggil lain, Laravel memanggil render(), jadi pengguna tetap melihat flash, bukan error 500.
 */
class ListingModerationException extends RuntimeException
{
    public static function draft(): self
    {
        return new self(__('listing-moderation.errors.draft'));
    }

    public static function alreadyHidden(): self
    {
        return new self(__('listing-moderation.errors.already_hidden'));
    }

    public static function alreadyVisible(): self
    {
        return new self(__('listing-moderation.errors.already_visible'));
    }

    public function render(): RedirectResponse
    {
        return back()->with('error', $this->getMessage());
    }
}
