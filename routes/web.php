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
