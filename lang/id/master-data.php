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
    ],

];
