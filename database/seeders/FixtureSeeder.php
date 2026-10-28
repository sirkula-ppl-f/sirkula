<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixtureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun Warga untuk testing login
        User::create([
            'name' => 'Warga Test',
            'email' => 'warga@sirkula.test',
            'password' => Hash::make('password'),
            // 'role' => 'WARGA', // Sesuaikan jika menggunakan enum/string
            // 'status' => 'ACTIVE',
        ]);

        // BAGIAN KATEGORI DIHAPUS KARENA SUDAH DI-HANDLE MASTER DATA SEEDER PBI-04

        // 3. Data Wilayah (Fixture untuk unblock PBI-07)
        DB::table('regions')->insert([
            ['name' => 'Bandung Kota', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bandung Barat', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Data Hub Komunitas (Fixture untuk unblock PBI-07)
        DB::table('hubs')->insert([
            ['name' => 'Bank Sampah Cicendo', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'RW 05 Dago', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Komunitas Buah Batu', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 5. Data Storefront (Etalase) dummy untuk user Warga Test (Dibutuhkan untuk Upcycle)
        DB::table('storefronts')->insert([
            'user_id' => 1, // ID dari Warga Test di atas
            'name' => 'Kriya Lembang',
            'slug' => 'kriya-lembang',
            'description' => 'Toko produk upcycle',
            'region_id' => 2, // Bandung Barat
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
