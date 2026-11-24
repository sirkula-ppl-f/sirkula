<?php

// PBI-06 (FR-020, FR-021): moderasi listing oleh admin.
// Riwayat moderasi append-only: sengaja tidak ada route ubah atau hapus riwayat.

use App\Http\Controllers\Admin\ListingModerationController;
use Illuminate\Support\Facades\Route;

// Admin → URL /admin/listing, nama route admin.listings.*
Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::controller(ListingModerationController::class)
            ->prefix('listing')
            ->name('listings.')
            ->group(function () {
                Route::get('/', 'index')->name('index');

                // Didaftarkan sebelum {listing} supaya tidak tertangkap sebagai id.
                Route::get('/riwayat-moderasi', 'moderations')->name('moderations');

                Route::get('/{listing}', 'show')->whereNumber('listing')->name('show');
                Route::patch('/{listing}/nonaktifkan', 'deactivate')->whereNumber('listing')->name('deactivate');
                Route::patch('/{listing}/pulihkan', 'restore')->whereNumber('listing')->name('restore');
            });
    });
