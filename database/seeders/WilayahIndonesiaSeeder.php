<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class WilayahIndonesiaSeeder extends Seeder
{
    public function run(): void
    {
        $wilayahList = [
            // Sumatera
            ['namaWilayah' => 'Aceh', 'kabupatenKota' => 'Kota Sabang', 'provinsi' => 'Aceh', 'tipeWilayah' => 'Kota'],
            ['namaWilayah' => 'Banda Aceh', 'kabupatenKota' => 'Kota Banda Aceh', 'provinsi' => 'Aceh', 'tipeWilayah' => 'Kota'],
            ['namaWilayah' => 'Sumatera Utara', 'kabupatenKota' => 'Kota Medan', 'provinsi' => 'Sumatera Utara', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sumatera Barat', 'kabupatenKota' => 'Kota Padang', 'provinsi' => 'Sumatera Barat', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Riau', 'kabupatenKota' => 'Kota Pekanbaru', 'provinsi' => 'Riau', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kepulauan Riau', 'kabupatenKota' => 'Kota Batam', 'provinsi' => 'Kepulauan Riau', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Jambi', 'kabupatenKota' => 'Kota Jambi', 'provinsi' => 'Jambi', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Bengkulu', 'kabupatenKota' => 'Kota Bengkulu', 'provinsi' => 'Bengkulu', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sumatera Selatan', 'kabupatenKota' => 'Kota Palembang', 'provinsi' => 'Sumatera Selatan', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kepulauan Bangka Belitung', 'kabupatenKota' => 'Kota Pangkalpinang', 'provinsi' => 'Kepulauan Bangka Belitung', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Lampung', 'kabupatenKota' => 'Kota Bandar Lampung', 'provinsi' => 'Lampung', 'tipeWilayah' => 'Provinsi'],

            // Jawa
            ['namaWilayah' => 'DKI Jakarta', 'kabupatenKota' => 'Jakarta Pusat', 'provinsi' => 'DKI Jakarta', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Bandung Kota', 'kabupatenKota' => 'Kota Bandung', 'provinsi' => 'Jawa Barat', 'tipeWilayah' => 'Kota'],
            ['namaWilayah' => 'Kabupaten Bandung', 'kabupatenKota' => 'Kab. Bandung', 'provinsi' => 'Jawa Barat', 'tipeWilayah' => 'Kabupaten'],
            ['namaWilayah' => 'Bandung Barat', 'kabupatenKota' => 'Kab. Bandung Barat', 'provinsi' => 'Jawa Barat', 'tipeWilayah' => 'Kabupaten'],
            ['namaWilayah' => 'Cimahi', 'kabupatenKota' => 'Kota Cimahi', 'provinsi' => 'Jawa Barat', 'tipeWilayah' => 'Kota'],
            ['namaWilayah' => 'Banten', 'kabupatenKota' => 'Kota Serang', 'provinsi' => 'Banten', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Jawa Tengah', 'kabupatenKota' => 'Kota Semarang', 'provinsi' => 'Jawa Tengah', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'DI Yogyakarta', 'kabupatenKota' => 'Kota Yogyakarta', 'provinsi' => 'DI Yogyakarta', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Jawa Timur', 'kabupatenKota' => 'Kota Surabaya', 'provinsi' => 'Jawa Timur', 'tipeWilayah' => 'Provinsi'],

            // Bali & Nusa Tenggara
            ['namaWilayah' => 'Bali', 'kabupatenKota' => 'Kota Denpasar', 'provinsi' => 'Bali', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Nusa Tenggara Barat', 'kabupatenKota' => 'Kota Mataram', 'provinsi' => 'Nusa Tenggara Barat', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Nusa Tenggara Timur', 'kabupatenKota' => 'Kota Kupang', 'provinsi' => 'Nusa Tenggara Timur', 'tipeWilayah' => 'Provinsi'],

            // Kalimantan
            ['namaWilayah' => 'Kalimantan Barat', 'kabupatenKota' => 'Kota Pontianak', 'provinsi' => 'Kalimantan Barat', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kalimantan Tengah', 'kabupatenKota' => 'Kota Palangka Raya', 'provinsi' => 'Kalimantan Tengah', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kalimantan Selatan', 'kabupatenKota' => 'Kota Banjarmasin', 'provinsi' => 'Kalimantan Selatan', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kalimantan Timur', 'kabupatenKota' => 'Kota Samarinda', 'provinsi' => 'Kalimantan Timur', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Kalimantan Utara', 'kabupatenKota' => 'Kota Tarakan', 'provinsi' => 'Kalimantan Utara', 'tipeWilayah' => 'Provinsi'],

            // Sulawesi
            ['namaWilayah' => 'Sulawesi Utara', 'kabupatenKota' => 'Kota Manado', 'provinsi' => 'Sulawesi Utara', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Gorontalo', 'kabupatenKota' => 'Kota Gorontalo', 'provinsi' => 'Gorontalo', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sulawesi Tengah', 'kabupatenKota' => 'Kota Palu', 'provinsi' => 'Sulawesi Tengah', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sulawesi Barat', 'kabupatenKota' => 'Kab. Mamuju', 'provinsi' => 'Sulawesi Barat', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sulawesi Selatan', 'kabupatenKota' => 'Kota Makassar', 'provinsi' => 'Sulawesi Selatan', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Sulawesi Tenggara', 'kabupatenKota' => 'Kota Kendari', 'provinsi' => 'Sulawesi Tenggara', 'tipeWilayah' => 'Provinsi'],

            // Maluku & Papua
            ['namaWilayah' => 'Maluku', 'kabupatenKota' => 'Kota Ambon', 'provinsi' => 'Maluku', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Maluku Utara', 'kabupatenKota' => 'Kota Ternate', 'provinsi' => 'Maluku Utara', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua', 'kabupatenKota' => 'Kota Jayapura', 'provinsi' => 'Papua', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua Barat', 'kabupatenKota' => 'Kota Sorong', 'provinsi' => 'Papua Barat', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua Tengah', 'kabupatenKota' => 'Kab. Nabire', 'provinsi' => 'Papua Tengah', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua Pegunungan', 'kabupatenKota' => 'Kab. Jayawijaya', 'provinsi' => 'Papua Pegunungan', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua Barat Daya', 'kabupatenKota' => 'Kota Sorong', 'provinsi' => 'Papua Barat Daya', 'tipeWilayah' => 'Provinsi'],
            ['namaWilayah' => 'Papua Selatan', 'kabupatenKota' => 'Kab. Merauke', 'provinsi' => 'Papua Selatan', 'tipeWilayah' => 'Kabupaten'],
        ];

        foreach ($wilayahList as $wilayah) {
            Region::create([
                'namaWilayah' => $wilayah['namaWilayah'],
                'kabupatenKota' => $wilayah['kabupatenKota'],
                'provinsi' => $wilayah['provinsi'],
                'tipeWilayah' => $wilayah['tipeWilayah'],
            ]);
        }
    }
}
