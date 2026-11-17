<?php

return [
    'nav' => 'Panduan',
    'title' => 'Panduan dan Edukasi',
    'subtitle' => 'Konten singkat yang tampil di form listing, halaman detail, dan dashboard',
    'back_dashboard' => 'Kembali ke Dashboard Admin',
    'back_index' => 'Kembali ke Panduan dan Edukasi',
    'create_button' => '+ Buat panduan',
    'create_title' => 'Buat panduan',
    'edit_title' => 'Ubah panduan',
    'edit' => 'Ubah',
    'empty' => 'Belum ada panduan.',

    'columns' => [
        'title' => 'Judul panduan',
        'status' => 'Status',
        'views' => 'Tayang',
        'interactions' => 'Interaksi',
    ],

    'preview' => [
        'title' => 'Pratinjau cuplikan',
        'shown_in' => 'Tampil di :places',
        'heading' => 'Panduan: :title',
        'privacy' => 'Sistem hanya mencatat jumlah tayang dan interaksi, tanpa data pribadi.',
    ],

    'form' => [
        'title' => 'Judul',
        'body' => 'Isi cuplikan',
        'body_hint' => 'Maksimal 500 karakter.',
        'placements' => 'Tampil di',
        'status' => 'Status',
        'save' => 'Simpan',
        'cancel' => 'Batal',
    ],

    'statuses' => [
        'draft' => 'Draf',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
    ],

    'placements' => [
        'listing_form' => 'Form listing',
        'detail_page' => 'Halaman detail',
        'dashboard' => 'Dashboard',
    ],

    'flash' => [
        'created' => 'Panduan dibuat.',
        'updated' => 'Panduan diperbarui.',
    ],

    'tip' => [
        'heading' => 'Panduan: :title',
        'ack' => 'Saya paham',
        'thanks' => 'Terima kasih!',
    ],

    'validation' => [
        'placements_required' => 'Pilih minimal satu lokasi tampil.',
        'placement_invalid' => 'Lokasi tampil yang dipilih tidak valid.',
    ],
];
