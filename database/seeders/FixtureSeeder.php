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
            // Hapus komentar di bawah jika kolom ini wajb diisi berdasarkan migration-mu
            // 'role' => 'WARGA',
            // 'status' => 'ACTIVE',
        ]);

        // 2. Data Kategori
        DB::table('categories')->insert([
            ['name' => 'Olahraga', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tas dan Aksesori', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Perabotan Rumah', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Data Wilayah
        DB::table('regions')->insert([
            ['name' => 'Bandung Kota', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bandung Barat', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Data Hub Komunitas
        DB::table('hubs')->insert([
            ['name' => 'Bank Sampah Cicendo', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'RW 05 Dago', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Komunitas Buah Batu', 'region_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
