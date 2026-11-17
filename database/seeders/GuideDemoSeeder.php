<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GuideDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('GuideDemoSeeder hanya untuk lingkungan local. Dilewati.');

            return;
        }

        $this->call(GuideSeeder::class);

        $stats = [
            'Memahami tipe listing' => [1240, 312],
            'Satuan lot untuk material' => [860, 190],
            'Batasan klaim dampak' => [420, 58],
        ];

        foreach ($stats as $title => [$views, $interactions]) {
            Guide::where('slug', Str::slug($title))->update([
                'views_count' => $views,
                'interactions_count' => $interactions,
            ]);
        }
    }
}
