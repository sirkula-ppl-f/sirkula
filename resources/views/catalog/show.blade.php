```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $listing['title'] }} - Sirkula</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #F7FAF7;
            color: #171C1A;
            font-family: Inter, Arial, sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        header {
            padding: 20px 5%;
            background: white;
            border-bottom: 1px solid #E3E8E6;
        }
        .brand {
            color: #178C5C;
            font-size: 21px;
            font-weight: 700;
        }
        main {
            max-width: 900px;
            margin: 35px auto;
            padding: 0 20px;
        }
        .back {
            display: inline-block;
            margin-bottom: 22px;
            color: #178C5C;
            font-weight: 600;
        }
        .detail {
            padding: 28px;
            background: white;
            border: 1px solid #E3E8E6;
            border-radius: 12px;
        }
        .badge {
            display: inline-block;
            padding: 6px 10px;
            background: #E0F5E8;
            color: #127345;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        h1 { margin: 18px 0 12px; }
        .price {
            color: #178C5C;
            font-size: 24px;
            font-weight: 700;
        }
        .meta {
            color: #6B7570;
            line-height: 1.8;
        }
        .description {
            margin-top: 28px;
            line-height: 1.8;
        }
        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            background: #178C5C;
            color: white;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ route('catalog.index') }}">Sirkula</a>
    </header>

    <main>
        <a class="back" href="{{ route('catalog.index') }}">
            &larr; Kembali ke katalog
        </a>

        <article class="detail">
            <span class="badge">{{ $listing['type'] }}</span>

            <h1>{{ $listing['title'] }}</h1>

            <p class="price">
                Rp{{ number_format($listing['price'], 0, ',', '.') }}
                @if(!empty($listing['unit']))
                    / {{ $listing['unit'] }}
                @endif
            </p>

            <div class="meta">
                <div>Kategori: {{ $listing['category'] }}</div>
                <div>Kondisi: {{ $listing['condition'] }}</div>
                <div>Wilayah: {{ $listing['region'] }}</div>
                <div>Hub: {{ $listing['hub'] }}</div>
                <div>Stok: {{ $listing['stock'] }}</div>
            </div>

            <section class="description">
                <h2>Deskripsi produk</h2>
                <p>{{ $listing['description'] }}</p>
            </section>

            <a class="button" href="{{ route('catalog.index') }}">
                Kembali ke katalog
            </a>
        </article>
    </main>
</body>
</html>
```