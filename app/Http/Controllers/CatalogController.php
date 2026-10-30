<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogController extends Controller
{
    private function sampleListings(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Sepeda Lipat Bekas',
                'type' => 'Barang Bekas',
                'category' => 'Olahraga dan Hobi',
                'condition' => 'Baik',
                'region' => 'DKI Jakarta',
                'hub' => 'Bank Sampah Cicendo',
                'price' => 850000,
                'unit' => 'unit',
                'stock' => 2,
                'created_at' => '2026-10-20',
                'description' => 'Sepeda lipat bekas yang masih layak digunakan.',
            ],
            [
                'id' => 2,
                'title' => 'Botol PET Terpilah',
                'type' => 'Material Daur Ulang',
                'category' => 'Botol dan Wadah',
                'condition' => 'Terpilah',
                'region' => 'Jawa Barat',
                'hub' => 'RW 05 Dago',
                'price' => 112500,
                'unit' => 'lot',
                'reference_price' => 4500,
                'stock' => 3,
                'created_at' => '2026-10-24',
                'description' => 'Botol PET terpilah dalam satu lot sekitar 25 kg.',
            ],
            [
                'id' => 3,
                'title' => 'Tas dari Banner Bekas',
                'type' => 'Produk Upcycle',
                'category' => 'Tas dan Dompet',
                'condition' => 'Baik',
                'region' => 'Jawa Tengah',
                'hub' => 'Etalase Kriya Lembang',
                'price' => 135000,
                'unit' => 'unit',
                'stock' => 4,
                'created_at' => '2026-10-23',
                'description' => 'Tas kreatif yang dibuat dari banner bekas.',
            ],
            [
                'id' => 4,
                'title' => 'Rak Buku Kayu 4 Susun',
                'type' => 'Barang Bekas',
                'category' => 'Perabot Rumah Tangga',
                'condition' => 'Baik',
                'region' => 'DI Yogyakarta',
                'hub' => 'Komunitas Buah Batu',
                'price' => 275000,
                'unit' => 'unit',
                'stock' => 2,
                'created_at' => '2026-10-22',
                'description' => 'Rak buku kayu bekas dengan empat susun.',
            ],
            [
                'id' => 5,
                'title' => 'Kardus Bekas',
                'type' => 'Material Daur Ulang',
                'category' => 'Kertas dan Kemasan Karton',
                'condition' => 'Bersih',
                'region' => 'Jawa Timur',
                'hub' => 'Bank Sampah Cicendo',
                'price' => 60000,
                'unit' => 'lot',
                'reference_price' => 1500,
                'stock' => 5,
                'created_at' => '2026-10-21',
                'description' => 'Kardus bekas bersih dalam lot sekitar 40 kg.',
            ],
            [
                'id' => 6,
                'title' => 'Dompet dari Ban Dalam',
                'type' => 'Produk Upcycle',
                'category' => 'Tas dan Dompet',
                'condition' => 'Baik',
                'region' => 'Bali',
                'hub' => 'Etalase Kriya Lembang',
                'price' => 95000,
                'unit' => 'unit',
                'stock' => 3,
                'created_at' => '2026-10-19',
                'description' => 'Dompet unik berbahan ban dalam bekas.',
            ],
            [
                'id' => 7,
                'title' => 'Kipas Angin Meja',
                'type' => 'Barang Bekas',
                'category' => 'Elektronik dan Gawai',
                'condition' => 'Cukup baik',
                'region' => 'Sumatera Utara',
                'hub' => 'RW 05 Dago',
                'price' => 120000,
                'unit' => 'unit',
                'stock' => 2,
                'created_at' => '2026-10-18',
                'description' => 'Kipas angin meja bekas.',
            ],
            [
                'id' => 8,
                'title' => 'Kaleng Aluminium',
                'type' => 'Material Daur Ulang',
                'category' => 'Kaleng dan Logam Bekas',
                'condition' => 'Terpilah',
                'region' => 'Sulawesi Selatan',
                'hub' => 'Komunitas Buah Batu',
                'price' => 180000,
                'unit' => 'lot',
                'reference_price' => 18000,
                'stock' => 4,
                'created_at' => '2026-10-17',
                'description' => 'Kaleng aluminium terpilah dalam lot sekitar 10 kg.',
            ],
        ];
    }

    public function index(Request $request)
    {
        $listings = collect($this->sampleListings());

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'newest');
        $type = (string) $request->query('type', '');
        $category = (string) $request->query('category', '');
        $condition = (string) $request->query('condition', '');
        $region = (string) $request->query('region', '');
        $hub = (string) $request->query('hub', '');

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');

        if ($search !== '') {
            $keyword = mb_strtolower($search);

            $listings = $listings->filter(function ($item) use ($keyword) {
                return str_contains(mb_strtolower($item['title']), $keyword)
                    || str_contains(mb_strtolower($item['description']), $keyword);
            });
        }

        foreach ([
            'type' => $type,
            'category' => $category,
            'condition' => $condition,
            'region' => $region,
            'hub' => $hub,
        ] as $field => $value) {
            if ($value !== '') {
                $listings = $listings->filter(
                    fn ($item) => $item[$field] === $value
                );
            }
        }

        if ($minPrice !== null && $minPrice !== '') {
            $listings = $listings->filter(
                fn ($item) => $item['price'] >= (float) $minPrice
            );
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $listings = $listings->filter(
                fn ($item) => $item['price'] <= (float) $maxPrice
            );
        }

        switch ($sort) {
            case 'price_asc':
                $listings = $listings->sortBy('price');
                break;

            case 'price_desc':
                $listings = $listings->sortByDesc('price');
                break;

            default:
                $listings = $listings->sortByDesc('created_at');
                $sort = 'newest';
                break;
        }

        $listings = $listings->values();

        $perPage = 12;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $paginatedListings = new LengthAwarePaginator(
            $listings->forPage($currentPage, $perPage),
            $listings->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('catalog.index', [
            'listings' => $paginatedListings,
            'search' => $search,
            'sort' => $sort,
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    public function show(int $id)
    {
        $listing = collect($this->sampleListings())
            ->firstWhere('id', $id);

        abort_if($listing === null, 404);

        return view('catalog.show', [
            'listing' => $listing,
        ]);
    }
}
