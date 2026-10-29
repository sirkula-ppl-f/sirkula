<?php

use App\Http\Controllers\Admin\HubController;
use App\Http\Controllers\Admin\HubManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // CRUD Hub
        Route::resource('hubs', HubController::class)
            ->except(['destroy']);

        // Arsipkan Hub
        Route::patch(
            'hubs/{hub}/archive',
            [HubController::class, 'archive']
        )->name('hubs.archive');

        // Penugasan pengelola
        Route::post(
            'hubs/{hub}/managers',
            [HubManagerController::class, 'store']
        )->name('hubs.managers.store');

        // Nonaktifkan penugasan
        Route::patch(
            'hubs/{hub}/managers/{manager}/deactivate',
            [HubManagerController::class, 'deactivate']
        )->name('hubs.managers.deactivate');
    });
