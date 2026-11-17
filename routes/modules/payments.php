<?php

// PBI-13 Pembayaran Midtrans Sandbox (FR-016)

use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('pembayaran')->name('payments.')->group(function () {
    Route::get('/{order}', [PaymentController::class, 'show'])->whereNumber('order')->name('show');
    Route::post('/{order}', [PaymentController::class, 'store'])->whereNumber('order')->middleware('throttle:10,1')->name('store');
    Route::get('/{order}/hasil', [PaymentController::class, 'result'])->whereNumber('order')->name('result');
});

// Dipanggil server Midtrans: tanpa login dan tanpa token CSRF. Keaslian dijaga tanda tangan.
Route::post('/webhook/midtrans', MidtransWebhookController::class)
    ->withoutMiddleware([PreventRequestForgery::class])
    ->middleware('throttle:120,1')
    ->name('payments.webhook');
