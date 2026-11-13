<?php

return [
    'index' => [
        'title' => 'Manajemen Hub',
        'description' => 'Kelola Hub komunitas Sirkula.',
        'add_button' => '+ Tambah Hub',
        'search_placeholder' => 'Cari nama Hub...',
        'all_statuses' => 'Semua Status',
        'search_button' => 'Cari',
        'actions' => 'Aksi',
        'detail_button' => 'Detail',
        'edit_button' => 'Edit',
        'archive_button' => 'Arsipkan',
        'empty' => 'Belum ada data Hub.',
    ],

    'create' => [
        'title' => 'Tambah Hub',
        'description' => 'Tambahkan bank sampah, RW, atau komunitas.',
    ],

    'edit' => [
        'title' => 'Edit Hub',
        'description' => 'Perbarui informasi Hub yang sudah terdaftar.',
    ],

    'show' => [
        'title' => 'Detail Hub',
        'description' => 'Detail dan pengelolaan Hub',
        'back' => 'Kembali',
        'edit_button' => 'Edit Hub',
        'information' => 'Informasi Hub',
    ],

    'fields' => [
        'name' => 'Nama Hub',
        'type' => 'Tipe Hub',
        'region' => 'Wilayah',
        'status' => 'Status',
        'public_address' => 'Alamat Publik',
        'description' => 'Deskripsi',
    ],

    'types' => [
        'BANK_SAMPAH' => 'Bank Sampah',
        'RW' => 'RW',
        'KOMUNITAS' => 'Komunitas',
    ],

    'status' => [
        'active' => 'Aktif',
        'archived' => 'Diarsipkan',
    ],

    'form' => [
        'choose_type' => 'Pilih tipe',
        'choose_region' => 'Pilih wilayah',
        'save' => 'Simpan Hub',
        'update' => 'Perbarui Hub',
        'cancel' => 'Batal',
    ],

    'manager' => [
        'title' => 'Pengelola Hub',
        'description' => 'Daftar pengguna yang pernah atau sedang ditugaskan sebagai pengelola Hub.',
        'assign_title' => 'Tugaskan Pengelola Baru',
        'choose_user' => 'Pilih pengguna',
        'assign_button' => 'Tugaskan',
        'no_available_users' => 'Tidak ada pengguna aktif yang tersedia untuk ditugaskan.',
        'archived_notice' => 'Hub sudah diarsipkan. Penugasan pengelola baru tidak tersedia.',
        'user' => 'Pengguna',
        'assigned_at' => 'Tanggal Penugasan',
        'status' => 'Status',
        'actions' => 'Aksi',
        'deactivate' => 'Nonaktifkan',
        'no_action' => 'Tidak ada aksi',
        'empty' => 'Belum ada pengelola yang ditugaskan ke Hub ini.',
        'unavailable_user' => 'Pengguna tidak tersedia',
        'deactivate_confirm' => 'Nonaktifkan :name dari Hub?',
        'deactivate_title' => 'Nonaktifkan Pengelola',
    ],

    'archive' => [
        'confirm_title' => 'Arsipkan Hub',
        'confirm_message' => 'Apakah Anda yakin ingin mengarsipkan Hub :name?',
        'confirm_button' => 'Arsipkan',
    ],

    'flash' => [
        'created' => 'Hub berhasil ditambahkan.',
        'updated' => 'Hub berhasil diperbarui.',
        'archived' => 'Hub berhasil diarsipkan.',
        'manager_assigned' => 'Pengelola Hub berhasil ditugaskan.',
        'manager_deactivated' => 'Penugasan pengelola berhasil dinonaktifkan.',
        'manager_already_inactive' => 'Penugasan sudah tidak aktif.',
    ],

    'validation' => [
        'errors_title' => 'Terdapat kesalahan pada input:',
    ],
];
