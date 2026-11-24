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

    /*
    |--------------------------------------------------------------------------
    | Navigasi, dashboard, dan flash
    |--------------------------------------------------------------------------
    */

    'nav' => [
        'section' => 'Moderasi',
        'admin' => 'Moderasi Listing',
    ],

    'dashboard' => [
        'title' => 'Moderasi Listing',
        'description' => 'Cari listing, nonaktifkan listing bermasalah beserta alasannya, dan lihat riwayat moderasi.',
        'link' => 'Buka Moderasi Listing',
        'hidden_count' => ':count dinonaktifkan',
    ],

    'flash' => [
        'deactivated' => 'Listing “:title” dinonaktifkan dan tidak lagi tampil di katalog.',
        'restored' => 'Listing “:title” dipulihkan.',
        'restored_not_visible' => 'Listing belum tampil di katalog karena: :reasons',
    ],

    'sale_unit' => [
        'ITEM' => 'per barang',
        'LOT' => 'per lot',
    ],

    'catalog' => [
        'yes' => 'Ya',
        'no' => 'Tidak',
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman admin
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'none' => '—',
        'listing_id' => 'ID :id',

        'index_title' => 'Moderasi Listing',
        'intro' => 'Cari dan filter listing yang sudah diajukan penjual (listing berstatus draf tidak ditampilkan). Buka detail listing untuk menonaktifkan atau memulihkannya beserta alasan.',
        'history_link' => 'Lihat riwayat moderasi',
        'filter_label' => 'Cari dan filter listing',
        'filter' => [
            'search' => 'Cari judul atau ID',
            'type' => 'Tipe listing',
            'all_types' => 'Semua tipe',
            'category' => 'Kategori',
            'all_categories' => 'Semua kategori',
            'hub' => 'Hub',
            'all_hubs' => 'Semua hub',
            'region' => 'Provinsi',
            'all_regions' => 'Semua provinsi',
            'review' => 'Status verifikasi',
            'all_reviews' => 'Semua status verifikasi',
            'moderation' => 'Status moderasi',
            'all_moderations' => 'Semua status moderasi',
            'apply' => 'Terapkan',
            'reset' => 'Reset',
        ],
        'inactive_category_option' => ':name (nonaktif)',
        'archived_hub_option' => ':name (diarsipkan)',
        'table_label' => 'Tabel listing',
        'table_caption' => 'Daftar listing, terbaru di atas, halaman :page dari :last',
        'columns' => [
            'listing' => 'Listing',
            'type' => 'Tipe',
            'category' => 'Kategori',
            'seller' => 'Penjual',
            'hub' => 'Hub',
            'region' => 'Wilayah',
            'price' => 'Harga',
            'review' => 'Status verifikasi',
            'moderation' => 'Status moderasi',
            'catalog' => 'Tampil di katalog',
            'action' => 'Aksi',
        ],
        'detail_link' => 'Detail',
        'detail_link_context' => 'listing :title',
        'empty' => 'Belum ada listing yang diajukan.',
        'empty_filtered' => 'Tidak ada listing yang cocok dengan filter.',
        'empty_reset' => 'Tampilkan semua listing',

        'show' => [
            'title' => 'Detail Listing',
            'back' => 'Kembali ke daftar listing',
            'info_title' => 'Informasi listing',
            'photo_alt' => 'Foto utama :title',
            'no_photo' => 'Belum ada foto.',
            'fields' => [
                'type' => 'Tipe',
                'category' => 'Kategori',
                'material' => 'Material',
                'condition' => 'Kondisi',
                'seller' => 'Penjual',
                'hub' => 'Hub',
                'region' => 'Wilayah',
                'price' => 'Harga',
                'stock' => 'Stok',
                'created_at' => 'Dibuat',
            ],
            'price_value' => ':price :unit',
            'seller_suspended' => 'Akun ditangguhkan',
            'hub_inactive' => 'Hub tidak aktif',
            'status_title' => 'Status listing',
            'status' => [
                'review' => 'Status verifikasi hub',
                'publish' => 'Status publikasi',
                'moderation' => 'Status moderasi',
                'catalog' => 'Tampil di katalog',
            ],
            'blockers_title' => 'Alasan listing tidak tampil di katalog:',
            'orders_title' => 'Pesanan',
            'orders_count' => 'Listing ini tercatat di :count pesanan.',
            'orders_note' => 'Moderasi tidak mengubah pesanan lama, dan pembayaran yang sedang berjalan tetap diproses.',
        ],

        'form' => [
            'deactivate_title' => 'Nonaktifkan listing',
            'deactivate_intro' => 'Listing langsung hilang dari katalog dan tidak bisa dimasukkan ke keranjang. Pesanan lama tidak berubah.',
            'deactivate_button' => 'Nonaktifkan',
            'restore_title' => 'Pulihkan listing',
            'restore_intro' => 'Pemulihan hanya mencabut penonaktifan oleh admin. Listing baru tampil lagi di katalog bila sudah disetujui hub, dipublikasikan, stoknya ada, serta penjual dan hub-nya aktif.',
            'restore_button' => 'Pulihkan',
            'reason' => 'Alasan',
            'reason_hint' => ':min–:max karakter. Alasan hanya terlihat oleh admin, tidak ditampilkan ke penjual atau di halaman publik.',
        ],

        'confirm' => [
            'deactivate_title' => 'Nonaktifkan listing?',
            'deactivate_message' => 'Listing “:title” akan langsung hilang dari katalog. Pesanan lama tidak berubah.',
            'deactivate_label' => 'Nonaktifkan',
            'restore_title' => 'Pulihkan listing?',
            'restore_message' => 'Listing “:title” akan dipulihkan, tetapi tetap harus lolos verifikasi hub dan syarat tayang lainnya sebelum tampil di katalog.',
            'restore_label' => 'Pulihkan',
        ],

        'history' => [
            'title' => 'Riwayat moderasi listing ini',
            'table_label' => 'Tabel riwayat moderasi listing ini',
            'table_caption' => 'Riwayat moderasi listing :title, terbaru di atas',
            'columns' => [
                'time' => 'Waktu',
                'action' => 'Aksi',
                'reason' => 'Alasan',
                'admin' => 'Admin',
                'request_id' => 'ID permintaan',
                'review_snapshot' => 'Status verifikasi saat itu',
            ],
            'empty' => 'Belum ada riwayat moderasi untuk listing ini.',
        ],

        'moderations' => [
            'title' => 'Riwayat Moderasi Listing',
            'intro' => 'Semua aksi moderasi listing oleh admin, terbaru di atas. Riwayat tidak dapat diubah atau dihapus. Tanggal mengikuti WIB.',
            'back' => 'Kembali ke daftar listing',
            'filter_label' => 'Filter riwayat moderasi',
            'filter' => [
                'action' => 'Aksi',
                'all_actions' => 'Semua aksi',
                'search' => 'Cari judul atau ID listing',
                'from' => 'Dari tanggal',
                'until' => 'Sampai tanggal',
                'apply' => 'Terapkan',
                'reset' => 'Reset',
            ],
            'table_label' => 'Tabel riwayat moderasi listing',
            'table_caption' => 'Riwayat moderasi listing, terbaru di atas, halaman :page dari :last',
            'columns' => [
                'time' => 'Waktu',
                'listing' => 'Listing',
                'action' => 'Aksi',
                'reason' => 'Alasan',
                'admin' => 'Admin',
                'request_id' => 'ID permintaan',
            ],
            'empty' => 'Belum ada riwayat moderasi listing.',
            'empty_filtered' => 'Tidak ada riwayat moderasi yang cocok dengan filter.',
            'empty_reset' => 'Tampilkan semua riwayat',
        ],
    ],

];
