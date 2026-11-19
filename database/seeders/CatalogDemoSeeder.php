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

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $penjual = collect(['demo.penjual1@sirkula.test', 'demo.penjual2@sirkula.test'])
            ->map(fn (string $email) => User::query()->firstOrCreate(
                ['email' => $email],
                User::factory()->make(['email' => $email])->getAttributes()
            ));

        // Hub dulu, wilayah mengikuti hub supaya konsisten dengan validasi form listing
        $hub = Hub::active()->first();
        if (! $hub) {
            $this->command?->warn('Belum ada hub aktif: hub_id dan region_id dikosongkan.');
        }

        // [judul, harga, stok, satuan, tipe, kondisi, deskripsi, kata kunci kategori, kata kunci material]
        $data = [
            ['Sepeda Lipat Bekas', 850000, 2, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepeda lipat masih layak pakai, rem dan rantai normal.', 'olahraga', null],
            ['Rak Buku Kayu 4 Susun', 275000, 3, 'ITEM', ListingType::Preloved, ItemCondition::Cukup, 'Rak kayu kokoh, tinggi 150 cm, ada sedikit goresan.', 'furnitur', null],
            ['Kipas Angin Berdiri', 120000, 4, 'ITEM', ListingType::Preloved, ItemCondition::SepertiBaru, 'Kipas tiga kecepatan, berfungsi normal.', 'elektronik', null],
            ['Buku Pelajaran SMA Paket 10', 90000, 5, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepuluh buku pelajaran, halaman lengkap.', 'buku', null],
            ['Botol PET Terpilah (lot 25 kg)', 112500, 6, 'LOT', ListingType::Recyclable, null, 'Botol PET bersih, label dilepas, siap olah.', 'plastik', 'pet'],
            ['Kardus Bekas Bersih (lot 50 kg)', 60000, 8, 'LOT', ListingType::Recyclable, null, 'Kardus kering dan terpilah per jenis.', 'kertas', 'kardus'],
            ['Tas dari Banner Bekas', 135000, 10, 'ITEM', ListingType::Upcycle, null, 'Tas jinjing buatan pengrajin lokal dari banner bekas.', 'tas', null],
            ['Dompet Anyaman Plastik Daur Ulang', 55000, 12, 'ITEM', ListingType::Upcycle, null, 'Dompet anyaman tangan dari plastik daur ulang.', 'aksesori', null],
        ];

        foreach ($data as $i => [$judul, $harga, $stok, $satuan, $tipe, $kondisi, $deskripsi, $kataKategori, $kataMaterial]) {
            $kategori = $this->kategori($tipe, $kataKategori);
            $material = $kataMaterial ? Material::active()->where('name', 'like', "%{$kataMaterial}%")->first() : null;

            if (! $kategori) {
                $this->command?->warn("Lewati '{$judul}': tidak ada kategori aktif untuk tipe {$tipe->value}.");

                continue;
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

    /** Kategori aktif yang berlaku untuk tipe listing; cocokkan nama dulu, kalau tidak ada ambil yang pertama. */
    private function kategori(ListingType $tipe, string $kata): ?Category
    {
        $query = fn () => Category::active()->forListingType($tipe)->orderBy('name');

        return $query()->where('name', 'like', "%{$kata}%")->first() ?? $query()->first();
    }
}
