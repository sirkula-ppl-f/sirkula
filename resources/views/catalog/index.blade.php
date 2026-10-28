
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Sirkula</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #F7FAF7; color: #171C1A; font-family: Inter, Arial, sans-serif; }
        a { text-decoration: none; color: inherit; }
        .navbar { min-height: 74px; background: white; padding: 16px 5%; display: flex; align-items: center; gap: 30px; border-bottom: 1px solid #E3E8E6; }
        .brand { color: #178C5C; font-size: 21px; font-weight: 700; white-space: nowrap; }
        .logo { display: inline-block; width: 25px; height: 25px; background: #178C5C; border-radius: 50%; vertical-align: middle; margin-right: 7px; }
        .navlinks { display: flex; align-items: center; gap: 26px; flex: 1; }
        .navlinks a { font-size: 14px; }
        .navlinks .active { color: #178C5C; font-weight: 700; }
        .btn { border: 1px solid #E3E8E6; background: white; border-radius: 8px; padding: 11px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-green { background: #178C5C; border-color: #178C5C; color: white; }
        main { max-width: 1440px; margin: auto; padding: 32px 5% 48px; }
        h1 { margin: 0 0 8px; font-size: 30px; }
        .subtitle { color: #6B7570; font-size: 14px; margin: 0 0 26px; }
        .search { display: flex; gap: 12px; margin-bottom: 20px; }
        .search input { flex: 1; min-width: 0; padding: 13px 16px; border: 1px solid #E3E8E6; border-radius: 9px; font: inherit; background: white; }
        .types { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
        .chip { display: inline-block; border: 1px solid #E3E8E6; background: white; padding: 9px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; }
        .chip.active { background: #178C5C; color: white; border-color: #178C5C; }
        .filters { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 22px; }
        .filters select, .filters input { max-width: 100%; border: 1px solid #E3E8E6; border-radius: 30px; background: white; padding: 10px 12px; font-size: 12px; }
        .filters input { width: 115px; }
        .results { font-size: 13px; color: #6B7570; margin: 0 0 18px; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
        .card { overflow: hidden; background: white; border: 1px solid #E3E8E6; border-radius: 12px; transition: box-shadow .15s; }
        .card:hover { box-shadow: 0 5px 18px #00000010; }
        .photo { height: 150px; display: flex; align-items: center; justify-content: center; background: #EDF2FC; color: #6B7570; font-size: 12px; }
        .photo.material { background: #EBF7F0; }
        .photo.upcycle { background: #FCF2E6; }
        .card-body { padding: 13px 14px 16px; }
        .badge { display: inline-block; margin-bottom: 9px; padding: 5px 9px; border-radius: 6px; font-size: 10px; font-weight: 700; background: #E7EDFF; color: #3158B7; }
        .badge.material { background: #E0F5E8; color: #127345; }
        .badge.upcycle { background: #FFEDD6; color: #BF610A; }
        .card h2 { margin: 0 0 9px; font-size: 15px; line-height: 1.45; }
        .price { margin: 0 0 8px; color: #178C5C; font-size: 16px; font-weight: 700; }
        .meta { margin: 0; color: #6B7570; font-size: 11px; line-height: 1.6; }
        .empty { grid-column: 1 / -1; background: white; border: 1px solid #E3E8E6; padding: 35px; text-align: center; border-radius: 12px; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 28px; }
        .pagination a, .pagination span { min-width: 36px; padding: 9px 12px; text-align: center; background: white; border: 1px solid #E3E8E6; border-radius: 8px; font-size: 13px; }
        .pagination .current { color: white; background: #178C5C; border-color: #178C5C; }
        @media(max-width: 1000px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .navbar { flex-wrap: wrap; } .navlinks { order: 3; flex-basis: 100%; } }
        @media(max-width: 560px) { .grid { grid-template-columns: 1fr; } main { padding: 24px 16px; } .navbar { padding: 16px; gap: 14px; } .navlinks { flex-wrap: wrap; gap: 14px; } .search { flex-wrap: wrap; } .search input { flex-basis: 100%; } h1 { font-size: 26px; } }
    </style>
</head>
<body>
<header class="navbar">
    <a class="brand" href="{{ route('catalog.index') }}"><span class="logo"></span>Sirkula</a>

    <nav class="navlinks">
        <a class="active" href="{{ route('catalog.index') }}">Katalog</a>
        <a href="#">Hub Komunitas</a>
        <a href="#">Harga Acuan</a>
        <a href="#">Panduan</a>
    </nav>

    <a class="btn" href="{{ route('login') }}">Masuk</a>
    <a class="btn btn-green" href="{{ route('register') }}">Daftar</a>
</header>

<main>
    <h1>Katalog Sirkula</h1>
    <p class="subtitle">Jelajahi barang bekas layak pakai, material daur ulang terpilah, dan produk upcycle dari berbagai wilayah di Indonesia.</p>

    <form class="search" method="GET" action="{{ route('catalog.index') }}">
        <input type="search" name="search" value="{{ $search }}" placeholder="Cari berdasarkan judul atau deskripsi...">
        <button class="btn btn-green" type="submit">Cari</button>
    </form>

    @php
        $types = [
            '' => 'Semua',
            'Barang Bekas' => 'Barang Bekas',
            'Material Daur Ulang' => 'Material Daur Ulang',
            'Produk Upcycle' => 'Produk Upcycle',
        ];
    @endphp

    <div class="types">
        @foreach($types as $value => $label)
            <a class="chip {{ request('type', '') === $value ? 'active' : '' }}"
               href="{{ route('catalog.index', array_merge(request()->except('page', 'type'), ['type' => $value])) }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form class="filters" method="GET" action="{{ route('catalog.index') }}">
        <input type="hidden" name="search" value="{{ $search }}">


<select name="category">
    <option value="">Semua Kategori</option>
    @foreach($categories as $categoryOption)
        <option
            value="{{ $categoryOption->name }}"
            @selected(request('category') === $categoryOption->name)
        >
            {{ $categoryOption->name }}
        </option>
    @endforeach
</select>



<select name="region">
    <option value="">Semua Wilayah</option>
    @foreach([
        'Aceh',
        'Sumatera Utara',
        'Sumatera Barat',
        'Riau',
        'Kepulauan Riau',
        'Jambi',
        'Sumatera Selatan',
        'Bangka Belitung',
        'Bengkulu',
        'Lampung',
        'DKI Jakarta',
        'Banten',
        'Jawa Barat',
        'Jawa Tengah',
        'DI Yogyakarta',
        'Jawa Timur',
        'Bali',
        'Nusa Tenggara Barat',
        'Nusa Tenggara Timur',
        'Kalimantan Barat',
        'Kalimantan Tengah',
        'Kalimantan Selatan',
        'Kalimantan Timur',
        'Kalimantan Utara',
        'Sulawesi Utara',
        'Gorontalo',
        'Sulawesi Tengah',
        'Sulawesi Barat',
        'Sulawesi Selatan',
        'Sulawesi Tenggara',
        'Maluku',
        'Maluku Utara',
        'Papua',
        'Papua Barat',
        'Papua Selatan',
        'Papua Tengah',
        'Papua Pegunungan',
        'Papua Barat Daya'
    ] as $option)
        <option value="{{ $option }}" @selected(request('region') === $option)>
            {{ $option }}
        </option>
    @endforeach
</select>


        <select name="hub">
            <option value="">Hub</option>
            @foreach(['Bank Sampah Cicendo','RW 05 Dago','Komunitas Buah Batu','Etalase Kriya Lembang'] as $option)
                <option value="{{ $option }}" @selected(request('hub') === $option)>{{ $option }}</option>
            @endforeach
        </select>

        <input type="number" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="Harga min">
        <input type="number" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="Harga maks">

        <select name="sort">
            <option value="newest" @selected($sort === 'newest')>Urutkan: Terbaru</option>
            <option value="price_asc" @selected($sort === 'price_asc')>Harga Terendah</option>
            <option value="price_desc" @selected($sort === 'price_desc')>Harga Tertinggi</option>
        </select>

        <button class="btn btn-green" type="submit">Terapkan</button>
        <a class="btn" href="{{ route('catalog.index') }}">Reset</a>
    </form>

    <p class="results">
        Menampilkan {{ $listings->count() }} listing di halaman ini
        ({{ $listings->total() }} hasil ditemukan)
    </p>

    <section class="grid">
        @forelse($listings as $listing)
            @php
                $type = $listing['type'] ?? '';
                $typeClass = $type === 'Material Daur Ulang'
                    ? 'material'
                    : ($type === 'Produk Upcycle' ? 'upcycle' : '');
            @endphp

            <a class="card" href="{{ route('catalog.show', $listing['id']) }}">
                <div class="photo {{ $typeClass }}">Foto produk</div>

                <div class="card-body">
                    <span class="badge {{ $typeClass }}">{{ $type ?: $listing['category'] }}</span>
                    <h2>{{ $listing['title'] }}</h2>

                    <p class="price">
                        Rp{{ number_format($listing['price'], 0, ',', '.') }}
                        @if(!empty($listing['unit']))
                            / {{ $listing['unit'] }}
                        @endif
                    </p>

                    <p class="meta">
                        {{ $listing['condition'] }}
                        ·
                        @if(!empty($listing['reference_price']))
                            Acuan Rp{{ number_format($listing['reference_price'], 0, ',', '.') }}/kg ·
                        @endif
                        Hub {{ $listing['hub'] }}
                    </p>
                </div>
            </a>
        @empty
            <div class="empty">
                <h2>Tidak ada listing ditemukan</h2>
                <p class="subtitle">Coba ubah kata kunci atau filter pencarian lo.</p>
                <a class="btn btn-green" href="{{ route('catalog.index') }}">Lihat semua katalog</a>
            </div>
        @endforelse
    </section>

    @if($listings->lastPage() > 1)
        <nav class="pagination" aria-label="Pagination">
            @if($listings->onFirstPage())
                <span>‹</span>
            @else
                <a href="{{ $listings->previousPageUrl() }}">‹</a>
            @endif

            @foreach($listings->getUrlRange(
                max(1, $listings->currentPage() - 2),
                min($listings->lastPage(), $listings->currentPage() + 2)
            ) as $page => $url)
                @if($page === $listings->currentPage())
                    <span class="current">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($listings->hasMorePages())
                <a href="{{ $listings->nextPageUrl() }}">›</a>
            @else
                <span>›</span>
            @endif
        </nav>
    @endif
</main>
</body>
</html>
