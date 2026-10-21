<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route umum dan autentikasi
|--------------------------------------------------------------------------
|
| Route login, registrasi, logout, dan reset kata sandi didaftarkan oleh
| Fortify. Route fitur/PBI ditaruh di routes/modules/*.php (lihat README).
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Warga (dan admin) yang sudah login dan aktif.
Route::middleware(['auth', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});

// Khusus admin.
Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| Route per modul
|--------------------------------------------------------------------------
|
| Semua file routes/modules/*.php dimuat otomatis, urut nama file.
|
*/

foreach (glob(__DIR__.'/modules/*.php') ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}
