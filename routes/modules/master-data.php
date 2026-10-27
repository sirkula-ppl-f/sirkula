<?php

// PBI-04 (FR-007): data master kategori dan jenis material (berlaku nasional).

use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

// Admin → URL /admin/kategori, nama route admin.categories.*
Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::controller(CategoryController::class)
            ->prefix('kategori')
            ->name('categories.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/tambah', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{category}/ubah', 'edit')->name('edit');
                Route::put('/{category}', 'update')->name('update');
                Route::delete('/{category}', 'destroy')->name('destroy');
                Route::patch('/{category}/nonaktifkan', 'deactivate')->name('deactivate');
                Route::patch('/{category}/aktifkan', 'activate')->name('activate');
            });
    });
