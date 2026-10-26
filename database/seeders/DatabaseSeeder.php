<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Hanya memanggil seeder per modul. Tambahkan seeder modul kamu di sini.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            MasterDataSeeder::class,
        ]);
    }
}
