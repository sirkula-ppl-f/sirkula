<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Pengelolaan Pengguna (PBI-19 / FR-004)
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
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    });
