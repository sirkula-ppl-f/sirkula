<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog - Sirkula</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F9FAFB] text-gray-800 font-sans">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-2 font-bold text-xl text-green-700">
                <div class="w-6 h-6 bg-green-600 rounded-full"></div>
                Sirkula
            </div>
            <div class="hidden md:flex gap-6 text-sm font-medium text-gray-600">
                <a href="#" class="text-gray-900 font-semibold">Katalog</a>
                <a href="#" class="hover:text-green-700">Hub Komunitas</a>
                <a href="#" class="hover:text-green-700">Harga Acuan</a>
                <a href="#" class="hover:text-green-700">Panduan</a>
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm font-semibold">
            <a href="#" class="px-4 py-2 border border-gray-300 rounded-full hover:bg-gray-50">Masuk</a>
            <a href="#" class="px-4 py-2 bg-[#1A8754] text-white rounded-full hover:bg-green-800">Daftar</a>
        </div>
    </nav>

    <!-- Header & Filter -->
    <div class="max-w-7xl mx-auto py-8 px-8">
        <h1 class="text-3xl font-bold mb-1">Katalog Sirkula</h1>
        <p class="text-gray-500 mb-8">Barang bekas layak pakai, material daur ulang terpilah, dan produk upcycle di Bandung Raya</p>
        
        <!-- Search Bar -->
        <div class="flex gap-4 mb-6">
            <input type="text" placeholder="Cari berdasarkan judul atau deskripsi..." class="flex-1 border-gray-300 rounded-lg p-3 border focus:outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600">
            <button class="bg-[#1A8754] text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-800">Cari</button>
        </div>

        <!-- Filter Tipe (Pills) -->
        <div class="flex gap-3 mb-6">
            <button class="bg-[#1A8754] text-white px-5 py-2 rounded-full text-sm font-semibold">Semua</button>
            <button class="bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-full text-sm font-semibold hover:bg-gray-50">Barang Bekas</button>
            <button class="bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-full text-sm font-semibold hover:bg-gray-50">Material Daur Ulang</button>
            <button class="bg-white border border-gray-300 text-gray-700 px-5 py-2 rounded-full text-sm font-semibold hover:bg-gray-50">Produk Upcycle</button>
        </div>

        <!-- Filter Dropdowns & Sorting -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex gap-3">
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Kategori <span class="text-[10px]">▼</span></button>
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Kondisi <span class="text-[10px]">▼</span></button>
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Wilayah <span class="text-[10px]">▼</span></button>
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Hub <span class="text-[10px]">▼</span></button>
                <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Rentang Harga <span class="text-[10px]">▼</span></button>
            </div>
            <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-1 hover:bg-gray-50">Urutkan: Terbaru <span class="text-[10px]">▼</span></button>
        </div>

        <p class="text-sm text-gray-500 mb-4">Menampilkan {{ $listings->count() }} dari {{ $listings->count() }} listing disetujui</p>

        <!-- Grid Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @forelse($listings as $listing)
                
                @php
                    // Menentukan warna kartu berdasarkan tipe listing
                    $bgClass = 'bg-[#F0F4FA] text-blue-600'; // Default Barang Bekas (Biru muda)
                    $pillClass = 'bg-blue-100 text-blue-600';
                    $lotText = '';
                    
                    if ($listing->type === 'RECYCLABLE') {
                        $bgClass = 'bg-[#EAF6ED] text-green-600'; // Daur Ulang (Hijau muda)
                        $pillClass = 'bg-green-100 text-green-700';
                        $lotText = ' / lot';
                    } elseif ($listing->type === 'UPCYCLE') {
                        $bgClass = 'bg-[#FEF5E7] text-orange-600'; // Upcycle (Jingga muda)
                        $pillClass = 'bg-orange-100 text-orange-600';
                    }
                @endphp

                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full cursor-pointer">
                    
                    <!-- Kotak Gambar dengan Warna Dinamis -->
                    <div class="w-full h-48 {{ $bgClass }} flex items-center justify-center relative">
                        @if($listing->images->isNotEmpty())
                            <img src="{{ asset('storage/' . $listing->images->first()->image_path) }}" alt="{{ $listing->title }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-sm font-medium opacity-50">Foto produk</span>
                        @endif
                    </div>

                    <!-- Konten Kartu -->
                    <div class="p-5 flex flex-col flex-1">
                        <!-- Label Tipe -->
                        <div class="mb-3">
                            <span class="text-[10px] font-bold px-2 py-1 rounded {{ $pillClass }}">
                                {{ $listing->type === 'RECYCLABLE' ? 'Material Daur Ulang' : ($listing->type === 'UPCYCLE' ? 'Produk Upcycle' : 'Barang Bekas') }}
                            </span>
                        </div>
                        
                        <h3 class="font-bold text-gray-900 leading-tight mb-2">{{ $listing->title }}</h3>
                        
                        <div class="text-[#1A8754] font-bold text-lg mb-2">
                            Rp{{ number_format($listing->price, 0, ',', '.') }}<span class="text-sm font-normal">{{ $lotText }}</span>
                        </div>
                        
                        <!-- Info Tambahan (di bagian bawah kartu) -->
                        <div class="mt-auto pt-2 text-xs text-gray-400">
                            @if($listing->type === 'RECYCLABLE')
                                Acuan Rp4.500/kg · Hub RW 05 Dago
                            @elseif($listing->type === 'UPCYCLE')
                                Etalase Kriya Lembang
                            @else
                                Kondisi baik · Hub Bank Sampah Cicendo
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-500">
                    Belum ada barang di katalog.
                </div>
            @endforelse
        </div>

        <!-- Pagination Sederhana (Mockup) -->
        <div class="flex justify-center items-center gap-2">
            <button class="w-8 h-8 rounded flex items-center justify-center border border-gray-300 text-gray-400 bg-white hover:bg-gray-50">&lsaquo;</button>
            <button class="w-8 h-8 rounded flex items-center justify-center bg-[#1A8754] text-white font-bold">1</button>
            <button class="w-8 h-8 rounded flex items-center justify-center border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium">2</button>
            <button class="w-8 h-8 rounded flex items-center justify-center border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium">3</button>
            <button class="w-8 h-8 rounded flex items-center justify-center border border-gray-300 text-gray-600 bg-white hover:bg-gray-50">&rsaquo;</button>
        </div>
    </div>

</body>
</html> 