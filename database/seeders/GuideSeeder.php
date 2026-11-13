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
            ['Memahami tipe listing', ['listing_form'], 'published', 1240, 312,
                'Pilih tipe listing sesuai kondisi material: dijual, dicari, atau dibagikan.'],

            // Contoh satu panduan tampil di dua lokasi

            ['Satuan lot untuk material', ['listing_form', 'detail_page'], 'published', 860, 190,
                'Material dijual per lot. Cantumkan berat total per lot agar pembeli bisa membandingkan dengan harga acuan.'],

            ['Menjual dengan bertanggung jawab', ['detail_page'], 'draft', 0, 0,
                'Pastikan material bersih, terpilah, dan sesuai deskripsi sebelum diterbitkan.'],

            ['Batasan klaim dampak', ['dashboard'], 'archived', 420, 58,
                'Klaim dampak lingkungan hanya boleh berdasarkan data yang bisa diverifikasi.'],
        ];

        foreach ($guides as [$title, $placements, $status, $views, $interactions, $body]) {
            $guide = Guide::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'status' => $status,
                    'body' => $body,
                    'views_count' => $views,
                    'interactions_count' => $interactions,
                ]
            );

            $guide->syncPlacements($placements);
        }
    }
}
