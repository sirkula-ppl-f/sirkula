<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hub;
use App\Models\Region;
use App\Models\User;

class DummyBypassSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'adelia@sirkula.test'],
            ['name' => 'Adelia', 'password' => bcrypt('password123')]
        );

        Hub::firstOrCreate(['nama' => 'Bank Sampah Cicendo']);
        Hub::firstOrCreate(['nama' => 'RW 05 Dago']);

        Region::firstOrCreate(
            ['name' => 'Bandung Kota'],
            ['code' => '3273']
        );
        Region::firstOrCreate(
            ['name' => 'Bandung Barat'],
            ['code' => '3217']
        );
    }
}