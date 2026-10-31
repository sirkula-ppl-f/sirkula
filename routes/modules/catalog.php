<?php

use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/katalog', [CatalogController::class, 'index'])
    ->name('catalog.index');

Route::get('/katalog/{id}', [CatalogController::class, 'show'])
    ->whereNumber('id')
    ->name('catalog.show');
