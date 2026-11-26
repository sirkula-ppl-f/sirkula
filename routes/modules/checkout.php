<?php

// PBI-12 Checkout

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::get('/{seller}', [CheckoutController::class, 'create'])
            ->whereNumber('seller')
            ->name('create');

        Route::post('/{seller}', [CheckoutController::class, 'store'])
            ->whereNumber('seller')
            ->name('store');
    });
