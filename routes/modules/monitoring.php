<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Dashboard Monitoring Admin & Ekspor CSV (PBI-21 / FR-022)
|--------------------------------------------------------------------------
|
| Hanya dapat diakses oleh user yang login, berstatus ACTIVE, dan memiliki
| role ADMIN.
|
*/

Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/monitoring/ekspor-csv', [AdminDashboardController::class, 'exportCsv'])
            ->name('monitoring.export');
    });
