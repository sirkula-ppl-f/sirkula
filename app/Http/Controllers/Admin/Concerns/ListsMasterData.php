<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Helper halaman daftar data master (kategori dan material).
 */
trait ListsMasterData
{
    /**
     * Nilai teks dari query string. Array (dari URL yang diubah manual) dan teks kosong dianggap tidak diisi.
     */
    protected function queryText(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Filter status dari query string: "aktif", "nonaktif", atau null untuk semua.
     */
    protected function statusFilter(Request $request): ?string
    {
        $status = $this->queryText($request, 'status');

        return in_array($status, ['aktif', 'nonaktif'], true) ? $status : null;
    }

    /**
     * Bila halaman yang diminta kosong (misalnya setelah data terakhir di halaman itu dihapus atau
     * dinonaktifkan), arahkan ke halaman terakhir yang berisi dengan filter yang sama. Flash dipertahankan.
     */
    protected function redirectIfPageEmpty(Request $request, LengthAwarePaginator $page): ?RedirectResponse
    {
        if ($page->isNotEmpty() || $page->currentPage() === 1) {
            return null;
        }

        $request->session()->reflash();

        return redirect($page->url(max($page->lastPage(), 1)));
    }
}
