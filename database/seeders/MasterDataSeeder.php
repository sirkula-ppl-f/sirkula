<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Material;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Isi kategori dan material contoh yang berlaku nasional (tidak per wilayah).
     *
     * Hanya membuat data yang slug-nya belum ada. Data yang sudah ada tidak disentuh sama sekali,
     * termasuk nama, deskripsi, status, dan tipe listing yang mungkin sudah diubah admin.
     */
    public function run(): void
    {
        foreach ($this->categories() as $data) {
            $category = Category::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'description' => $data['description']],
            );

            if ($category->wasRecentlyCreated) {
                $category->categoryListingTypes()->createMany(
                    array_map(fn (ListingType $type) => ['listing_type' => $type], $data['types'])
                );
            }
        }

        foreach ($this->materials() as $data) {
            Material::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'description' => $data['description']],
            );
        }
    }

    /**
     * @return list<array{slug: string, name: string, description: string, types: list<ListingType>}>
     */
    private function categories(): array
    {
        $preloved = ListingType::Preloved;
        $recyclable = ListingType::Recyclable;
        $upcycle = ListingType::Upcycle;

        return [
            [
                'slug' => 'pakaian-dan-aksesori',
                'name' => 'Pakaian dan Aksesori',
                'description' => 'Baju, celana, sepatu, topi, dan aksesori yang masih layak pakai, termasuk produk olahan dari kain bekas.',
                'types' => [$preloved, $upcycle],
            ],
            [
                'slug' => 'elektronik-dan-gawai',
                'name' => 'Elektronik dan Gawai',
                'description' => 'Ponsel, laptop, peralatan audio, dan perangkat elektronik rumah tangga yang masih berfungsi.',
                'types' => [$preloved],
            ],
            [
                'slug' => 'perabot-rumah-tangga',
                'name' => 'Perabot Rumah Tangga',
                'description' => 'Meja, kursi, lemari, rak, dan perlengkapan dapur yang masih layak pakai.',
                'types' => [$preloved, $upcycle],
            ],
            [
                'slug' => 'buku-dan-alat-tulis',
                'name' => 'Buku dan Alat Tulis',
                'description' => 'Buku pelajaran, novel, majalah, dan perlengkapan sekolah atau kantor.',
                'types' => [$preloved],
            ],
            [
                'slug' => 'perlengkapan-bayi-dan-anak',
                'name' => 'Perlengkapan Bayi dan Anak',
                'description' => 'Kereta dorong, mainan, pakaian anak, dan perlengkapan bayi yang masih layak pakai.',
                'types' => [$preloved],
            ],
            [
                'slug' => 'olahraga-dan-hobi',
                'name' => 'Olahraga dan Hobi',
                'description' => 'Sepeda, alat olahraga, alat musik, dan perlengkapan hobi lainnya.',
                'types' => [$preloved],
            ],
            [
                'slug' => 'tas-dan-dompet',
                'name' => 'Tas dan Dompet',
                'description' => 'Tas, ransel, dan dompet bekas, termasuk produk olahan dari bahan bekas.',
                'types' => [$preloved, $upcycle],
            ],
            [
                'slug' => 'botol-dan-wadah',
                'name' => 'Botol dan Wadah',
                'description' => 'Botol minuman, galon, jeriken, dan wadah plastik atau kaca yang sudah dipilah.',
                'types' => [$recyclable],
            ],
            [
                'slug' => 'kertas-dan-kemasan-karton',
                'name' => 'Kertas dan Kemasan Karton',
                'description' => 'Kertas bekas, koran, kardus, dan kemasan karton dalam keadaan kering.',
                'types' => [$recyclable],
            ],
            [
                'slug' => 'kaleng-dan-logam-bekas',
                'name' => 'Kaleng dan Logam Bekas',
                'description' => 'Kaleng minuman, kaleng makanan, dan potongan logam dari peralatan rumah tangga.',
                'types' => [$recyclable],
            ],
            [
                'slug' => 'barang-elektronik-rusak',
                'name' => 'Barang Elektronik Rusak',
                'description' => 'Perangkat elektronik yang tidak berfungsi, kabel, dan komponennya untuk didaur ulang.',
                'types' => [$recyclable],
            ],
            [
                'slug' => 'kerajinan-tangan',
                'name' => 'Kerajinan Tangan',
                'description' => 'Produk kerajinan yang dibuat dari bahan sisa atau barang bekas.',
                'types' => [$upcycle],
            ],
            [
                'slug' => 'dekorasi-rumah',
                'name' => 'Dekorasi Rumah',
                'description' => 'Hiasan dinding, pot tanaman, lampu hias, dan dekorasi lain dari bahan daur ulang.',
                'types' => [$upcycle],
            ],
        ];
    }

    /**
     * @return list<array{slug: string, name: string, description: string}>
     */
    private function materials(): array
    {
        return [
            ['slug' => 'plastik-pet', 'name' => 'Plastik PET', 'description' => 'Plastik berkode daur ulang 1, misalnya botol air mineral dan botol minuman bening.'],
            ['slug' => 'plastik-hdpe', 'name' => 'Plastik HDPE', 'description' => 'Plastik berkode daur ulang 2, misalnya jeriken, botol sampo, dan botol deterjen.'],
            ['slug' => 'plastik-ldpe', 'name' => 'Plastik LDPE', 'description' => 'Plastik berkode daur ulang 4, misalnya kantong plastik dan plastik pembungkus.'],
            ['slug' => 'plastik-pp', 'name' => 'Plastik PP', 'description' => 'Plastik berkode daur ulang 5, misalnya gelas plastik, tutup botol, dan wadah makanan.'],
            ['slug' => 'kertas', 'name' => 'Kertas', 'description' => 'Kertas HVS, buku tulis, koran, dan majalah bekas dalam keadaan kering.'],
            ['slug' => 'kardus', 'name' => 'Kardus', 'description' => 'Kardus dan karton bergelombang bekas kemasan.'],
            ['slug' => 'aluminium', 'name' => 'Aluminium', 'description' => 'Kaleng minuman, foil, dan peralatan berbahan aluminium.'],
            ['slug' => 'besi-dan-baja', 'name' => 'Besi dan Baja', 'description' => 'Potongan besi, baja, dan kaleng makanan berbahan logam besi.'],
            ['slug' => 'tembaga', 'name' => 'Tembaga', 'description' => 'Kabel dan komponen berbahan tembaga.'],
            ['slug' => 'kaca', 'name' => 'Kaca', 'description' => 'Botol, toples, dan wadah berbahan kaca.'],
            ['slug' => 'kain-dan-tekstil', 'name' => 'Kain dan Tekstil', 'description' => 'Pakaian yang tidak layak pakai, kain perca, dan bahan tekstil lainnya.'],
            ['slug' => 'kayu', 'name' => 'Kayu', 'description' => 'Palet, potongan kayu, dan perabot kayu yang tidak terpakai.'],
            ['slug' => 'komponen-elektronik', 'name' => 'Komponen Elektronik', 'description' => 'Papan sirkuit, adaptor, dan komponen dari perangkat elektronik rusak.'],
        ];
    }
}
