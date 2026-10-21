# Route per modul

Setiap PBI/modul menaruh route-nya di file sendiri di folder ini, misalnya `routes/modules/listing.php`. Tujuannya supaya anggota tim tidak bentrok saat mengedit `routes/web.php`.

- Semua file `*.php` di folder ini dimuat otomatis oleh `routes/web.php`, **urut nama file**.
- Middleware `web` (sesi, CSRF, cookie) sudah otomatis terpasang. Tidak perlu ditambahkan lagi.
- Jangan menaruh route login/registrasi/logout/reset kata sandi di sini. Route itu milik Fortify.
- Nama route wajib unik di seluruh aplikasi. Awali dengan nama modul, misalnya `listing.index`.

## Grup middleware

| Grup | Siapa yang boleh | Middleware |
|---|---|---|
| Tamu/publik | semua orang | tanpa tambahan |
| Warga | user login yang statusnya ACTIVE (warga maupun admin) | `['auth', 'active']` |
| Admin | user login, ACTIVE, dengan role ADMIN | `['auth', 'active', 'role:ADMIN']` + prefix `admin` + nama `admin.` |

- `auth`: tamu diarahkan ke halaman login.
- `active`: user yang statusnya SUSPENDED langsung di-logout dan diarahkan ke login dengan pesan.
- `role:ADMIN`: user dengan role lain mendapat 403.
- Pengelola Hub **bukan role**. Pengecekannya lewat penugasan di tabel `hub_managers` (PBI-01), biasanya memakai Policy.

## Contoh file modul

```php
<?php

// routes/modules/listing.php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

// Publik
Route::get('/barang', [ListingController::class, 'index'])->name('listing.index');

// Warga
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/barang/baru', [ListingController::class, 'create'])->name('listing.create');
    Route::post('/barang', [ListingController::class, 'store'])->name('listing.store');
});

// Admin → URL /admin/barang, nama route admin.listing.index
Route::middleware(['auth', 'active', 'role:ADMIN'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/barang', [ListingController::class, 'adminIndex'])->name('listing.index');
    });
```

Cek hasilnya dengan `php artisan route:list --path=barang`.
