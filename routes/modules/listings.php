<?php

use App\Http\Controllers\ListingController;

Route::middleware('auth')->group(function () {

    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
});
