<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moderasi Listing (PBI-06)
    |--------------------------------------------------------------------------
    |
    | Admin menonaktifkan atau memulihkan visibilitas listing dengan alasan.
    | Alasan moderasi hanya terlihat oleh admin, tidak pernah di halaman publik.
    |
    */

    'moderation_status' => [
        'VISIBLE' => 'Diizinkan tampil',
        'HIDDEN' => 'Dinonaktifkan admin',
    ],

    'action' => [
        'DEACTIVATE' => 'Dinonaktifkan',
        'RESTORE' => 'Dipulihkan',
    ],

    // Nilai lain yang tidak dikenal ditampilkan apa adanya.
    'review_status' => [
        'DRAFT' => 'Draf',
        'PENDING_REVIEW' => 'Menunggu verifikasi hub',
        'APPROVED' => 'Disetujui hub',
        'REJECTED' => 'Ditolak hub',
    ],

    'publish_status' => [
        'ACTIVE' => 'Dipublikasikan',
        'INACTIVE' => 'Tidak dipublikasikan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alasan listing tidak tampil di katalog
    |--------------------------------------------------------------------------
    |
    | Urutannya sama dengan syarat Listing::scopePurchasable().
    |
    */

    'blockers' => [
        'moderated' => 'Dinonaktifkan admin.',
        'review_pending' => 'Menunggu verifikasi hub.',
        'review_not_approved' => 'Belum atau tidak disetujui hub.',
        'not_published' => 'Tidak dipublikasikan penjual.',
        'out_of_stock' => 'Stok habis.',
        'seller_inactive' => 'Akun penjual ditangguhkan.',
        'hub_inactive' => 'Hub listing tidak aktif.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penolakan aturan bisnis
    |--------------------------------------------------------------------------
    */

    'errors' => [
        'forbidden' => 'Hanya admin yang dapat memoderasi listing.',
        'already_hidden' => 'Listing ini sudah dinonaktifkan. Muat ulang halaman untuk melihat status terbaru.',
        'already_visible' => 'Listing ini tidak sedang dinonaktifkan. Muat ulang halaman untuk melihat status terbaru.',
        'draft' => 'Listing berstatus draf belum pernah diajukan, jadi tidak dapat dimoderasi.',
    ],

    'immutable' => [
        'update' => 'Riwayat moderasi listing tidak dapat diubah. Catat aksi moderasi baru bila status perlu diubah.',
        'delete' => 'Riwayat moderasi listing tidak dapat dihapus.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validasi alasan
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'reason' => 'alasan',
    ],

    'validation' => [
        'reason_required' => 'Alasan moderasi wajib diisi.',
        'reason_string' => 'Alasan moderasi harus berupa teks.',
        'reason_min' => 'Alasan moderasi minimal :min karakter.',
        'reason_max' => 'Alasan moderasi maksimal :max karakter.',
    ],

];
