<?php

namespace Database\Seeders;

use App\Enums\ItemCondition;
use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
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

        $kategori = Category::query()->first() ?? Category::factory()->create();
        $hubId = Hub::query()->value('id');
        $regionId = Region::query()->value('id');

        $data = [
            ['Sepeda Lipat Bekas', 850000, 2, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepeda lipat masih layak pakai, rem dan rantai normal.'],
            ['Rak Buku Kayu 4 Susun', 275000, 3, 'ITEM', ListingType::Preloved, ItemCondition::Cukup, 'Rak kayu kokoh, tinggi 150 cm, ada sedikit goresan.'],
            ['Kipas Angin Berdiri', 120000, 4, 'ITEM', ListingType::Preloved, ItemCondition::SepertiBaru, 'Kipas tiga kecepatan, berfungsi normal.'],
            ['Buku Pelajaran SMA Paket 10', 90000, 5, 'ITEM', ListingType::Preloved, ItemCondition::Baik, 'Sepuluh buku pelajaran, halaman lengkap.'],
            ['Botol PET Terpilah (lot 25 kg)', 112500, 6, 'LOT', ListingType::Recyclable, null, 'Botol PET bersih, label dilepas, siap olah.'],
            ['Kardus Bekas Bersih (lot 50 kg)', 60000, 8, 'LOT', ListingType::Recyclable, null, 'Kardus kering dan terpilah per jenis.'],
            ['Tas dari Banner Bekas', 135000, 10, 'ITEM', ListingType::Upcycle, null, 'Tas jinjing buatan pengrajin lokal dari banner bekas.'],
            ['Dompet Anyaman Plastik Daur Ulang', 55000, 12, 'ITEM', ListingType::Upcycle, null, 'Dompet anyaman tangan dari plastik daur ulang.'],
        ];

        foreach ($data as $i => [$judul, $harga, $stok, $satuan, $tipe, $kondisi, $deskripsi]) {
            Listing::query()->updateOrCreate(
                ['title' => $judul, 'user_id' => $penjual[$i % 2]->id],
                [
                    'category_id' => $kategori->id,
                    'listing_type' => $tipe->value,
                    'price' => $harga,
                    'stock' => $stok,
                    'sale_unit' => $satuan,
                    'description' => $deskripsi,
                    'item_condition' => $kondisi?->value,
                    'hub_id' => $hubId,
                    'region_id' => $regionId,
                    'review_status' => Listing::REVIEW_APPROVED,
                    'publish_status' => Listing::PUBLISH_ACTIVE,
                ]
            );
        }
    }
}