<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Akun Admin Awal
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh AdminSeeder untuk membuat akun admin pertama. Isi nilainya
    | di .env masing-masing, jangan di .env.example. Jika salah satu kosong,
    | AdminSeeder akan dilewati.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
