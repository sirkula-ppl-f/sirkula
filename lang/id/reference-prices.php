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

    'nav' => [
        'public' => 'Harga Acuan',
        'admin' => 'Kelola Harga Acuan',
    ],

    'status' => [
        'current' => 'Berlaku',
        'scheduled' => 'Terjadwal',
        'history' => 'Riwayat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validasi form terbitkan
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'material_id' => 'material',
        'region_id' => 'provinsi',
        'price_per_kg' => 'harga per kg',
        'effective_date' => 'tanggal berlaku',
        'source' => 'sumber harga',
    ],

    'validation' => [
        'material_required' => 'Pilih material.',
        'material_inactive' => 'Pilih material yang masih aktif.',
        'region_required' => 'Pilih provinsi.',
        'region_not_province' => 'Pilih provinsi dari daftar. Harga acuan hanya diterbitkan per provinsi.',
        'price_required' => 'Harga per kg wajib diisi.',
        'price_integer' => 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.',
        'price_min' => 'Harga per kg minimal :min.',
        'price_max' => 'Harga per kg paling besar :max.',
        'date_required' => 'Tanggal berlaku wajib diisi.',
        'date_format' => 'Tanggal berlaku harus berupa tanggal yang valid.',
        'date_min' => 'Tanggal berlaku paling awal :date.',
        'date_max' => 'Tanggal berlaku paling lambat :date (:days hari dari hari ini).',
        'source_required' => 'Sumber harga wajib diisi.',
        'source_string' => 'Sumber harga harus berupa teks.',
        'source_max' => 'Sumber harga maksimal :max karakter.',
    ],

    'flash' => [
        'published' => 'Harga acuan :material di :region sebesar :price berhasil diterbitkan, berlaku sejak :date.',
        'scheduled' => 'Harga acuan :material di :region sebesar :price berhasil diterbitkan dan terjadwal berlaku mulai :date.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman admin
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'index_title' => 'Kelola Harga Acuan',
        'create_title' => 'Terbitkan Harga Acuan',
        'intro' => 'Harga acuan berlaku per provinsi dalam rupiah per kg. Harga ini bersifat informatif dan tidak mengubah harga yang ditetapkan penjual.',
        'append_only' => 'Riwayat harga tidak dapat diubah atau dihapus. Untuk mengoreksi salah input, terbitkan entri baru (boleh dengan tanggal berlaku yang sama); entri yang terbit paling akhir yang dipakai.',
        'create_link' => 'Terbitkan harga',
        'filter_label' => 'Filter riwayat harga acuan',
        'filter' => [
            'material' => 'Material',
            'all_materials' => 'Semua material',
            'region' => 'Provinsi',
            'all_regions' => 'Semua provinsi',
            'from' => 'Berlaku dari',
            'until' => 'Berlaku sampai',
            'apply' => 'Terapkan',
            'reset' => 'Reset',
        ],
        'inactive_material_option' => ':name (nonaktif)',
        'table_label' => 'Tabel riwayat harga acuan',
        'table_caption' => 'Riwayat harga acuan, terbaru di atas, halaman :page dari :last',
        'columns' => [
            'material' => 'Material',
            'region' => 'Provinsi',
            'price' => 'Harga/kg',
            'effective_date' => 'Tanggal berlaku',
            'status' => 'Status',
            'source' => 'Sumber',
            'publisher' => 'Penerbit',
            'published_at' => 'Waktu terbit',
        ],
        'empty' => 'Belum ada harga acuan yang diterbitkan.',
        'empty_filtered' => 'Tidak ada harga acuan yang cocok dengan filter.',

        'form' => [
            'intro' => 'Pilih material dan provinsi, lalu isi harga per kg, tanggal berlaku, dan sumber harga.',
            'material' => 'Material',
            'material_placeholder' => 'Pilih material',
            'region' => 'Provinsi',
            'region_placeholder' => 'Pilih provinsi',
            'price' => 'Harga per kg (Rp)',
            'price_hint' => 'Rupiah bulat tanpa titik atau koma, misalnya 3500. Paling besar :max.',
            'effective_date' => 'Tanggal berlaku',
            'effective_date_hint' => 'Terisi tanggal hari ini (WIB). Bisa dari :min sampai :max. Tanggal setelah hari ini berlabel Terjadwal sampai tanggal itu tiba.',
            'source' => 'Sumber harga',
            'source_hint' => 'Misalnya nama survei, laporan, atau lembaga sumber harga. Maksimal 255 karakter.',
            'submit' => 'Terbitkan',
            'cancel' => 'Batal',
        ],

        'current' => [
            'title' => 'Harga yang sedang berlaku',
            'found' => ':material di :region: :price, berlaku sejak :date. Sumber: :source.',
            'none' => 'Belum ada harga acuan yang berlaku untuk :material di :region.',
        ],

        'confirm' => [
            'title' => 'Terbitkan harga acuan?',
            'message' => 'Harga acuan yang diterbitkan tidak dapat diubah atau dihapus. Pastikan material, provinsi, harga, tanggal berlaku, dan sumber sudah benar.',
            'label' => 'Terbitkan',
        ],

        'dashboard' => [
            'title' => 'Harga Acuan',
            'description' => 'Terbitkan harga acuan material per provinsi dan lihat riwayatnya.',
            'link' => 'Buka Harga Acuan',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman publik
    |--------------------------------------------------------------------------
    */

    'public' => [
        'index_title' => 'Harga Acuan Material',
        'intro' => 'Harga acuan material daur ulang dalam rupiah per kg untuk setiap provinsi, lengkap dengan tanggal berlaku dan sumber harganya.',
        'informative' => 'Harga acuan bersifat informatif dan tidak mengikat harga penjual. Harga jual tetap ditentukan oleh masing-masing penjual, dan setiap harga acuan hanya berlaku untuk provinsi yang tercantum.',
        'filter_label' => 'Filter harga acuan',
        'filter' => [
            'material' => 'Material',
            'all_materials' => 'Semua material',
            'region' => 'Provinsi',
            'all_regions' => 'Semua provinsi',
            'apply' => 'Terapkan',
            'reset' => 'Reset',
        ],
        'table_label' => 'Tabel harga acuan terbaru',
        'table_caption' => 'Harga acuan terbaru per material dan provinsi, halaman :page dari :last',
        'columns' => [
            'material' => 'Material',
            'region' => 'Provinsi',
            'price' => 'Harga/kg',
            'effective_date' => 'Berlaku sejak',
            'source' => 'Sumber',
            'history' => 'Riwayat',
        ],
        'history_link' => 'Lihat riwayat',
        'history_link_context' => 'harga :material di :region',
        'empty' => 'Belum ada harga acuan yang berlaku.',
        'empty_filtered' => 'Tidak ada harga acuan yang cocok dengan filter.',
        'empty_reset' => 'Tampilkan semua harga acuan',

        'show_title' => ':material di :region',
        'back' => 'Kembali ke daftar harga acuan',
        'latest' => [
            'title' => 'Harga terbaru',
            'effective_date' => 'Berlaku sejak',
            'source' => 'Sumber',
        ],
        'trend' => [
            'title' => 'Tren harga',
            'intro' => 'Satu titik untuk setiap tanggal berlaku, paling banyak :max tanggal terakhir. Angka lengkapnya ada di tabel riwayat.',
        ],
        'history' => [
            'title' => 'Riwayat harga',
            'intro' => 'Semua harga yang sudah berlaku, terbaru di atas. Koreksi diterbitkan sebagai entri baru; bila tanggal berlakunya sama, entri yang terbit paling akhir yang dipakai.',
            'table_label' => 'Tabel riwayat harga',
            'table_caption' => 'Riwayat harga :material di :region, terbaru di atas, halaman :page dari :last',
            'columns' => [
                'effective_date' => 'Tanggal berlaku',
                'price' => 'Harga/kg',
                'source' => 'Sumber',
                'status' => 'Status',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grafik tren (<x-reference-price.trend-chart>)
    |--------------------------------------------------------------------------
    */

    'chart' => [
        'title' => 'Grafik tren harga acuan :material di :region',
        'summary' => 'Harga dari :from_price (:from_date) menjadi :to_price (:to_date), :count data.',
        'summary_single' => 'Harga :price sejak :date, 1 data.',
        'single_point' => 'Baru ada satu data harga.',
        'point' => ':date: :price',
    ],

    'demo_seeder' => [
        'source' => 'Data contoh untuk demo',
        'not_local' => 'ReferencePriceDemoSeeder dibatalkan: DB aktif bukan DB lokal. Seeder ini hanya untuk DB lokal, bukan DB online tim.',
        'already_seeded' => 'Harga acuan demo sudah ada, seeder dilewati. Entri harga acuan tidak dapat dihapus, jadi seeder tidak menambah data ganda.',
        'missing_admin' => 'Belum ada akun admin. Jalankan AdminSeeder lebih dulu.',
        'missing_master_data' => 'Material aktif atau provinsi belum ada. Jalankan MasterDataSeeder dan WilayahIndonesiaSeeder lebih dulu.',
        'done' => ':count harga acuan demo diterbitkan.',
    ],

];
