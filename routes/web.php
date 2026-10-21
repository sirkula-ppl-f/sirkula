<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

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

// Fortify tidak memasang rate limit di POST reset kata sandi. Kedua route ini
// dideklarasi ulang dengan controller Fortify yang sama, ditambah throttle.
// File ini dimuat setelah route Fortify, sehingga route di bawah menimpanya.
Route::middleware(['guest:'.config('fortify.guard'), 'throttle:password-reset'])->group(function () {
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
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
