<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GuideSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            ['Memahami tipe listing', ['listing_form'], 'published',
                'Pilih tipe listing sesuai kondisi material: dijual, dicari, atau dibagikan.'],
            ['Satuan lot untuk material', ['listing_form', 'detail_page'], 'published',
                'Material dijual per lot. Cantumkan berat total per lot agar pembeli bisa membandingkan dengan harga acuan.'],
            ['Menjual dengan bertanggung jawab', ['detail_page'], 'draft',
                'Pastikan material bersih, terpilah, dan sesuai deskripsi sebelum diterbitkan.'],
            ['Batasan klaim dampak', ['dashboard'], 'archived',
                'Klaim dampak lingkungan hanya boleh berdasarkan data yang bisa diverifikasi.'],
        ];

        foreach ($guides as [$title, $placements, $status, $body]) {
            $guide = Guide::firstOrCreate(
                ['slug' => Str::slug($title)],
                ['title' => $title, 'status' => $status, 'body' => $body]
            );

            if ($guide->wasRecentlyCreated) {
                $guide->syncPlacements($placements);
            }
        }
    }
}
