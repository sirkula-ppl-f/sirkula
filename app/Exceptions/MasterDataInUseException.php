<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Data master yang sudah dipakai tidak boleh dihapus, hanya dinonaktifkan.
 *
 * Laravel memanggil render() saat exception ini tidak ditangkap, jadi admin dikembalikan ke
 * halaman sebelumnya dengan flash "error", bukan melihat error 500.
 */
class MasterDataInUseException extends RuntimeException
{
    public function render(): RedirectResponse
    {
        return back()->with('error', $this->getMessage());
    }
}
