<?php

// PBI-11 Keranjang Belanja (FR-014)

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('keranjang')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::patch('/{cartItem}', [CartController::class, 'update'])->whereNumber('cartItem')->name('update');
    Route::delete('/{cartItem}', [CartController::class, 'destroy'])->whereNumber('cartItem')->name('destroy');
});
