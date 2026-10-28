<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            [
                'province' => 'Jawa Barat',
                'name' => 'Kota Bandung',
                'type' => 'KOTA',
            ],
            [
                'province' => 'Jawa Barat',
                'name' => 'Kabupaten Bandung',
                'type' => 'KABUPATEN',
            ],
            [
                'province' => 'Jawa Barat',
                'name' => 'Kabupaten Bandung Barat',
                'type' => 'KABUPATEN',
            ],
            [
                'province' => 'Jawa Barat',
                'name' => 'Kota Cimahi',
                'type' => 'KOTA',
            ],
            [
                'province' => 'DKI Jakarta',
                'name' => 'Kota Jakarta Selatan',
                'type' => 'KOTA',
            ],
        ];

        foreach ($regions as $region) {
            Region::firstOrCreate($region);
        }
    }
}