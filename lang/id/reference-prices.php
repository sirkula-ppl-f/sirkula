<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Harga Acuan Material (PBI-05)
    |--------------------------------------------------------------------------
    |
    | Harga acuan berlaku per provinsi, dalam rupiah per kg. Riwayatnya
    | append-only: entri yang sudah terbit tidak dapat diubah atau dihapus.
    |
    */

    'immutable' => [
        'update' => 'Harga acuan yang sudah terbit tidak dapat diubah. Terbitkan entri baru untuk mengoreksinya.',
        'delete' => 'Harga acuan yang sudah terbit tidak dapat dihapus. Terbitkan entri baru untuk mengoreksinya.',
    ],

    'publish_forbidden' => 'Hanya admin yang dapat menerbitkan harga acuan.',

    'demo_seeder' => [
        'source' => 'Data contoh untuk demo',
        'not_local' => 'ReferencePriceDemoSeeder dibatalkan: DB aktif bukan DB lokal. Seeder ini hanya untuk DB lokal, bukan DB online tim.',
        'already_seeded' => 'Harga acuan demo sudah ada, seeder dilewati. Entri harga acuan tidak dapat dihapus, jadi seeder tidak menambah data ganda.',
        'missing_admin' => 'Belum ada akun admin. Jalankan AdminSeeder lebih dulu.',
        'missing_master_data' => 'Material aktif atau provinsi belum ada. Jalankan MasterDataSeeder dan WilayahIndonesiaSeeder lebih dulu.',
        'done' => ':count harga acuan demo diterbitkan.',
    ],

];
