<?php

use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\GuideTrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/panduan', [GuideController::class, 'index'])->name('guides.index');
        Route::get('/panduan/buat', [GuideController::class, 'create'])->name('guides.create');
        Route::post('/panduan', [GuideController::class, 'store'])->name('guides.store');
        Route::get('/panduan/{guide}/ubah', [GuideController::class, 'edit'])->name('guides.edit');
        Route::put('/panduan/{guide}', [GuideController::class, 'update'])->name('guides.update');
    });

Route::middleware('throttle:60,1')
    ->prefix('panduan')
    ->name('guides.')
    ->group(function () {
        Route::post('/{guide}/tayang', [GuideTrackingController::class, 'view'])->name('view');
        Route::post('/{guide}/interaksi', [GuideTrackingController::class, 'interact'])->name('interact');
    });
