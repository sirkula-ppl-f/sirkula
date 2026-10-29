<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Master: Kategori
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'created' => 'Kategori ":name" berhasil ditambahkan.',
        'updated' => 'Kategori ":name" berhasil diperbarui.',
        'deactivated' => 'Kategori ":name" dinonaktifkan. Listing baru tidak dapat memakainya.',
        'activated' => 'Kategori ":name" diaktifkan kembali.',
        'deleted' => 'Kategori ":name" berhasil dihapus.',
        'in_use' => 'Kategori sudah dipakai listing; hanya dapat dinonaktifkan.',
        'type_in_use' => 'Tipe listing :types masih dipakai listing dengan kategori ini, jadi tidak dapat dihapus.',
        'types_in_use_hint' => 'Masih dipakai listing: :types. Tipe ini tidak dapat dihapus dari kategori.',
        'empty' => 'Belum ada kategori.',
        'empty_filtered' => 'Tidak ada kategori yang cocok dengan pencarian atau filter.',
        'table_label' => 'Tabel daftar kategori',
        'table_caption' => 'Daftar kategori, halaman :page dari :last',
        'nav' => 'Kategori',
        'confirm' => [
            'deactivate_title' => 'Nonaktifkan kategori?',
            'deactivate' => 'Nonaktifkan kategori “:name”? Kategori nonaktif tidak bisa dipilih untuk listing baru, tetapi listing lama tetap tersimpan.',
            'delete_title' => 'Hapus kategori?',
            'delete' => 'Hapus kategori “:name”? Tindakan ini tidak bisa dibatalkan.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Master: Jenis Material
    |--------------------------------------------------------------------------
    */

    'materials' => [
        'created' => 'Material ":name" berhasil ditambahkan.',
        'updated' => 'Material ":name" berhasil diperbarui.',
        'deactivated' => 'Material ":name" dinonaktifkan. Listing baru tidak dapat memakainya.',
        'activated' => 'Material ":name" diaktifkan kembali.',
        'deleted' => 'Material ":name" berhasil dihapus.',
        'in_use' => 'Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.',
        'empty' => 'Belum ada material.',
        'empty_filtered' => 'Tidak ada material yang cocok dengan pencarian atau filter.',
        'table_label' => 'Tabel daftar material',
        'table_caption' => 'Daftar material, halaman :page dari :last',
        'nav' => 'Material',
        'confirm' => [
            'deactivate_title' => 'Nonaktifkan material?',
            'deactivate' => 'Nonaktifkan material “:name”? Material nonaktif tidak bisa dipilih untuk listing baru, tetapi listing dan harga acuan lama tetap tersimpan.',
            'delete_title' => 'Hapus material?',
            'delete' => 'Hapus material “:name”? Tindakan ini tidak bisa dibatalkan.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dialog konfirmasi (teks umum)
    |--------------------------------------------------------------------------
    |
    | Dipakai <x-confirm-dialog /> dan <x-master-data.row-actions> bila
    | teks khusus master-data.{entity}.confirm.* tidak ada.
    |
    */

    'confirm' => [
        'title' => 'Konfirmasi tindakan',
        'confirm_label' => 'Lanjutkan',
        'cancel_label' => 'Batal',
        'deactivate_title' => 'Nonaktifkan data?',
        'deactivate' => 'Nonaktifkan “:name”? Data nonaktif tidak bisa dipilih lagi, tetapi data yang sudah memakainya tetap tersimpan.',
        'deactivate_label' => 'Nonaktifkan',
        'delete_title' => 'Hapus data?',
        'delete' => 'Hapus “:name”? Tindakan ini tidak bisa dibatalkan.',
        'delete_label' => 'Hapus',
    ],

];
