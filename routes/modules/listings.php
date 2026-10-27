<?php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route; // <- BARIS INI WAJIB ADA

Route::get('/', function () {
    return view('welcome');
});

// Rute untuk membuat listing (Hanya untuk user yang sudah login)
// Matikan middleware(['auth']) sementara jika kamu belum membuat fitur login
Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
