<?php

namespace Database\Seeders;

use App\Enums\ItemCondition;
use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk demo katalog (PBI-10).
 *
 * CARA PAKAI: untuk DB LOKAL. Dijalankan ke DB online hanya bila grup sepakat dan oleh satu orang,
 * karena listing-nya tampil di katalog semua orang. Seeder ini sengaja tidak didaftarkan di
 * DatabaseSeeder. Aman dijalankan berulang (updateOrCreate).
 *
 *   php artisan db:seed --class=CatalogDemoSeeder
 */
class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $host = (string) config('database.connections.'.config('database.default').'.host');

        // Pengaman: DB selain lokal harus dikonfirmasi (perintah non-interaktif otomatis batal).
        if (! in_array($host, ['127.0.0.1', 'localhost'], true)
            && ! $this->command?->confirm("DB aktif '{$host}' bukan lokal, data demo akan terlihat semua orang. Lanjut?", false)) {
            $this->command?->warn('Dibatalkan.');

            return;
        }

        $penjual = collect(['demo.penjual1@sirkula.test', 'demo.penjual2@sirkula.test'])
            ->map(fn (string $email) => User::query()->firstOrCreate(
                ['email' => $email],
                User::factory()->make(['email' => $email])->getAttributes()
            ));

        // Hub dulu, wilayah mengikuti hub supaya konsisten dengan validasi form listing.
        $hub = Hub::active()->first();
        if (! $hub) {
            $this->command?->warn('Belum ada hub aktif: hub_id dan region_id dikosongkan.');
        }

        // [judul, harga, stok, satuan, tipe, kondisi, deskripsi, slug kategori, nama material]
        $data = [
            ['Sepeda Lipat Bekas', 850000, 2, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepeda lipat masih layak pakai, rem dan rantai normal.', 'olahraga-dan-hobi', null],
            ['Rak Buku Kayu 4 Susun', 275000, 3, 'ITEM', ListingType::Preloved, ItemCondition::Cukup, 'Rak kayu kokoh, tinggi 150 cm, ada sedikit goresan.', 'perabot-rumah-tangga', null],
            ['Kipas Angin Berdiri', 120000, 4, 'ITEM', ListingType::Preloved, ItemCondition::SepertiBaru, 'Kipas tiga kecepatan, berfungsi normal.', 'elektronik-dan-gawai', null],
            ['Buku Pelajaran SMA Paket 10', 90000, 5, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepuluh buku pelajaran, halaman lengkap.', 'buku-dan-alat-tulis', null],
            ['Botol PET Terpilah (lot 25 kg)', 112500, 6, 'LOT', ListingType::Recyclable, null, 'Botol PET bersih, label dilepas, siap olah.', 'botol-dan-wadah', 'Plastik PET'],
            ['Kardus Bekas Bersih (lot 50 kg)', 60000, 8, 'LOT', ListingType::Recyclable, null, 'Kardus kering dan terpilah per jenis.', 'kertas-dan-kemasan-karton', 'Kardus'],
            ['Tas dari Banner Bekas', 135000, 10, 'ITEM', ListingType::Upcycle, null, 'Tas jinjing buatan pengrajin lokal dari banner bekas.', 'kerajinan-tangan', null],
            ['Dompet Anyaman Plastik Daur Ulang', 55000, 12, 'ITEM', ListingType::Upcycle, null, 'Dompet anyaman tangan dari plastik daur ulang.', 'kerajinan-tangan', null],
        ];

        foreach ($data as $i => [$judul, $harga, $stok, $satuan, $tipe, $kondisi, $deskripsi, $slugKategori, $namaMaterial]) {
            $kategori = $this->kategori($tipe, $slugKategori);
            $material = $namaMaterial ? $this->material($namaMaterial) : null;

            if (! $kategori) {
                $this->command?->warn("Lewati '{$judul}': tidak ada kategori aktif untuk tipe {$tipe->value}.");

                continue;
            }

            if ($tipe === ListingType::Recyclable && ! $material) {
                $this->command?->warn("'{$judul}': material '{$namaMaterial}' tidak ketemu, material_id kosong.");
            }

            Listing::query()->updateOrCreate(
                ['title' => $judul, 'user_id' => $penjual[$i % 2]->id],
                [
                    'category_id' => $kategori->id,
                    'material_id' => $material?->id,
                    'listing_type' => $tipe->value,
                    'price' => $harga,
                    'stock' => $stok,
                    'sale_unit' => $satuan,
                    'description' => $deskripsi,
                    'item_condition' => $kondisi?->value,
                    'hub_id' => $hub?->id,
                    'region_id' => $hub?->region_id,
                    'review_status' => Listing::REVIEW_APPROVED,
                    'publish_status' => Listing::PUBLISH_ACTIVE,
                ]
            );
        }
    }

    /** Kategori aktif untuk tipe listing, dicocokkan lewat slug; cadangan: kategori pertama yang berlaku. */
    private function kategori(ListingType $tipe, string $slug): ?Category
    {
        $query = fn () => Category::active()->forListingType($tipe)->orderBy('name');

        return $query()->where('slug', $slug)->first() ?? $query()->first();
    }

    private function material(string $nama): ?Material
    {
        return Material::active()->where('name', $nama)->first();
    }
}
