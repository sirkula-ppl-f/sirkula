<?php

use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

// Route PBI-16: Kelola Profil Publik
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::patch('/profil', [ProfilController::class, 'update'])->name('profil.update');
});
