<?php

use App\Http\Controllers\Admin\HubController;
use App\Http\Controllers\Admin\HubManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('pusat-komunitas', [HubController::class, 'index'])
            ->name('hubs.index');

        Route::get('pusat-komunitas/tambah', [HubController::class, 'create'])
            ->name('hubs.create');

        Route::post('pusat-komunitas', [HubController::class, 'store'])
            ->name('hubs.store');

        Route::get('pusat-komunitas/{hub}/ubah', [HubController::class, 'edit'])
            ->name('hubs.edit');

        Route::match(
            ['PUT', 'PATCH'],
            'pusat-komunitas/{hub}',
            [HubController::class, 'update'])
            ->name('hubs.update');

        Route::get('pusat-komunitas/{hub}', [HubController::class, 'show'])
            ->name('hubs.show');

        Route::patch(
            'pusat-komunitas/{hub}/arsipkan',
            [HubController::class, 'archive']
        )->name('hubs.archive');

        Route::post(
            'pusat-komunitas/{hub}/pengelola',
            [HubManagerController::class, 'store']
        )->name('hubs.managers.store');

        Route::patch(
            'pusat-komunitas/{hub}/pengelola/{manager}/nonaktifkan',
            [HubManagerController::class, 'deactivate']
        )->name('hubs.managers.deactivate');
    });
