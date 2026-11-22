<?php

use App\Http\Controllers\CircularActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catatan Aktivitas Sirkular (PBI-20 / FR-022)
|--------------------------------------------------------------------------
|
| Hanya untuk warga yang login dan berstatus ACTIVE. Setiap warga hanya
| melihat aktivitas miliknya sendiri.
|
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/aktivitas-sirkular', [CircularActivityController::class, 'index'])
        ->name('circular-activity.index');
});
