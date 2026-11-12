<?php

// PBI-05 (FR-008): harga acuan material per provinsi (Rp/kg).
// Riwayat append-only: sengaja tidak ada route ubah atau hapus.

use App\Http\Controllers\Admin\ReferencePriceController as AdminReferencePriceController;
use Illuminate\Support\Facades\Route;

// Admin → URL /admin/harga-acuan, nama route admin.reference-prices.*
Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::controller(AdminReferencePriceController::class)
            ->prefix('harga-acuan')
            ->name('reference-prices.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/terbitkan', 'create')->name('create');
                Route::post('/', 'store')->name('store');
            });
    });
