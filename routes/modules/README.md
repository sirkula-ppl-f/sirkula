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
- `active`: user yang statusnya SUSPENDED langsung di-logout dan diarahkan ke login dengan pesan. Middleware ini sudah terpasang global di grup `web` (bootstrap/app.php), sehingga berlaku juga di halaman publik. Tetap tulis `active` di grup supaya maksud route terlihat jelas.
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

## Data master: kategori dan jenis material (PBI-04)

Kategori produk dan jenis material berlaku **nasional** (seluruh Indonesia). Tabelnya tidak punya `region_id` atau kolom wilayah lain, dan teks UI tidak memakai istilah lokal daerah tertentu. Admin mengelolanya di `/admin/kategori` dan `/admin/material`. Data yang sudah dipakai hanya bisa dinonaktifkan, tidak bisa dihapus.

### Tipe listing dan tabel

`App\Enums\ListingType` disimpan sebagai string di `category_listing_types.listing_type` dan `listings.listing_type`.

| Case | Nilai | `label()` |
|---|---|---|
| `Preloved` | `PRELOVED` | Barang Bekas |
| `Recyclable` | `RECYCLABLE` | Material Daur Ulang |
| `Upcycle` | `UPCYCLE` | Produk Upcycle |

| Tabel | Kolom |
|---|---|
| `categories` | `id`, `name` (maks. 100), `slug` (unik, tidak berubah setelah dibuat), `description`, `is_active`, timestamps |
| `category_listing_types` | `category_id` (cascade saat kategori dihapus), `listing_type`; unik per pasangan |
| `materials` | `id`, `name` (maks. 100), `slug` (unik, tidak berubah), `description`, `is_active`, timestamps |

### Model dan scope

```php
// Pilihan di form listing: hanya data aktif, kategori yang berlaku untuk tipe listing itu.
Category::active()->forListingType(ListingType::Preloved)->orderBy('name')->get();
Material::active()->orderBy('name')->get();

$category->listingTypes();       // Collection<ListingType>, urut sesuai enum
$category->isInUse();            // sudah dipakai? (lihat di bawah)
Category::withInUse()->paginate(); // atribut in_use lewat subquery, tanpa query per baris
```

"Dipakai" berarti ada baris di `listings.category_id` (kategori), atau di `listings.material_id` / `reference_prices.material_id` (material). Pengecekan memakai `DB::table()`, bukan relasi Eloquent, jadi **semua baris dihitung**, termasuk listing yang di-soft-delete, ditolak, atau nonaktif. Tabel atau kolom yang belum ada dilewati (`Schema::hasColumn`).

### Validasi listing baru

Pakai rule ini di FormRequest listing (PBI-07/08/09). Kategori/material nonaktif ditolak, kategori yang tidak berlaku untuk tipe listing juga ditolak. Listing lama yang sudah memakainya tidak ikut berubah.

```php
use App\Enums\ListingType;
use App\Rules\ActiveCategoryForType;
use App\Rules\ActiveMaterial;
use Illuminate\Validation\Rule;

public function rules(): array
{
    // Tipe dipilih di form: pakai tryFrom(), bukan from(), karena input bisa tidak valid.
    $type = ListingType::tryFrom((string) $this->input('listing_type'));

    return [
        'listing_type' => ['required', Rule::enum(ListingType::class)],
        'category_id' => ['required', 'integer', ...($type ? [new ActiveCategoryForType($type)] : [])],
        'material_id' => ['required', 'integer', new ActiveMaterial],
    ];
}
```

Bila form hanya untuk satu tipe, cukup `new ActiveCategoryForType(ListingType::Preloved)`.

### Kewajiban migrasi PBI lain

Pengecekan "Dipakai" dan larangan hapus hanya berlaku bila nama kolom dan FK-nya seperti ini:

```php
// PBI-07/08: migrasi listings asli (menggantikan stub dari PR #8)
$table->string('listing_type', 20);
$table->foreignId('category_id')->constrained()->restrictOnDelete();
$table->foreignId('material_id')->constrained()->restrictOnDelete(); // boleh ->nullable() bila tidak semua tipe butuh material

// PBI-05: migrasi reference_prices
$table->foreignId('material_id')->constrained()->restrictOnDelete();
```

`tests/Feature/Admin/MasterDataListingUsageTest.php` ikut menguji FK `restrictOnDelete` di tabel `listings`, jadi test itu harus tetap lulus setelah stub diganti.

### Route

| Nama route | Method | URL kategori |
|---|---|---|
| `admin.categories.index` | GET | `/admin/kategori` |
| `admin.categories.create` | GET | `/admin/kategori/tambah` |
| `admin.categories.store` | POST | `/admin/kategori` |
| `admin.categories.edit` | GET | `/admin/kategori/{category}/ubah` |
| `admin.categories.update` | PUT | `/admin/kategori/{category}` |
| `admin.categories.destroy` | DELETE | `/admin/kategori/{category}` |
| `admin.categories.deactivate` | PATCH | `/admin/kategori/{category}/nonaktifkan` |
| `admin.categories.activate` | PATCH | `/admin/kategori/{category}/aktifkan` |

`admin.materials.*` punya nama aksi yang sama dengan URL `/admin/material/...` dan parameter `{material}`. Semuanya di grup admin (`['auth', 'active', 'role:ADMIN']`), file `routes/modules/master-data.php`.

### Factory dan seeder

```php
Category::factory()->create();                                  // aktif, berlaku untuk SEMUA tipe listing
Category::factory()->forTypes(ListingType::Upcycle)->create();  // hanya tipe yang disebut
Category::factory()->inactive()->create();
Material::factory()->inactive()->create();
```

`MasterDataSeeder` (dipanggil `DatabaseSeeder`) memakai `firstOrCreate` berdasarkan `slug`. Data yang sudah ada tidak disentuh, termasuk nama, status, dan tipe listing hasil ubahan admin. Tipe listing hanya dipasang saat kategori baru dibuat. Aman dijalankan berulang kali.

### Dialog konfirmasi `<x-confirm-dialog />`

Untuk aksi berisiko di modul lain, beri form atribut `data-confirm`, lalu pasang `<x-confirm-dialog />` **sekali di view halaman itu** (bukan di layout):

```blade
<form method="POST" action="{{ route('modul.hapus', $data) }}" data-submit-once
      data-confirm="Hapus “{{ $data->nama }}”? Tindakan ini tidak bisa dibatalkan."
      data-confirm-title="Hapus data?"
      data-confirm-label="Hapus"
      data-confirm-variant="danger">
    @csrf
    @method('DELETE')
    <button type="submit">Hapus</button>
</form>

<x-confirm-dialog />
```

| Atribut | Wajib | Isi |
|---|---|---|
| `data-confirm` | ya | Pesan di dialog |
| `data-confirm-title` | tidak | Judul (bawaan "Konfirmasi tindakan") |
| `data-confirm-label` | tidak | Teks tombol konfirmasi (bawaan "Lanjutkan") |
| `data-confirm-variant` | tidak | `danger` untuk tombol merah pada aksi yang tidak bisa dibatalkan |

- Fokus awal di tombol Batal. Batal, Esc, atau klik backdrop menutup dialog tanpa mengirim form, lalu fokus kembali ke tombol pemicu.
- Cocok dengan `data-submit-once`: tombol baru dinonaktifkan setelah dikonfirmasi.
- Tanpa JavaScript, tanpa `<x-confirm-dialog />` di halaman, atau di browser tanpa `showModal()`/`requestSubmit()` (Safari < 16), form langsung terkirim tanpa dialog. Karena itu aturan penting tetap dijaga di server.
- Untuk aksi baris data master, pakai `<x-master-data.row-actions>` dengan prop `entity` (teks dialog dari `lang/id/master-data.php`).
- Wadah tabel `overflow-x-auto` yang memuat `<x-master-data.row-actions>` wajib diberi kelas `relative`. Tanpa itu, span `sr-only` (`position: absolute`) di tombol aksi lolos dari wadah dan halaman bergeser horizontal di layar sempit.

### Tautan navbar

`$links` di `resources/views/components/layouts/app.blade.php` menerima dua key opsional:

```php
$links[] = ['label' => 'Hub', 'route' => 'admin.hubs.index', 'active' => 'admin.hubs.*', 'dusk' => 'nav-hubs'];
```

- `active`: pola `request()->routeIs()` untuk menandai tautan aktif (`aria-current="page"`). Bawaannya nilai `route`.
- `dusk`: atribut `dusk` pada tautan, untuk selector tes Dusk.
