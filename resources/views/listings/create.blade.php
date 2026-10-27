<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
        <a href="{{ route('listings.index') }}" class="text-green-700 hover:underline mb-4 inline-block font-medium">&larr; Kembali ke Listing Saya</a>
        <h1 class="text-3xl font-bold mb-8">Buat Listing Baru</h1>

        <!-- Form membungkus seluruh grid (kiri dan kanan) -->
        <form action="{{ route('listings.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
          <input type="hidden" name="listing_type" id="type_input" value="{{ old('listing_type', 'PRELOVED') }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- KOLOM KIRI: Form Utama -->
                <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-200">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-3">Tipe listing</label>
                        <div class="flex space-x-3">
                            <button type="button" onclick="setType('PRELOVED')" id="btn_PRELOVED" class="type-btn px-5 py-2.5 bg-green-700 text-white rounded-full text-sm font-semibold">Barang Bekas</button>
                            <button type="button" onclick="setType('RECYCLABLE')" id="btn_RECYCLABLE" class="type-btn px-5 py-2.5 border border-gray-300 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-50">Material Daur Ulang</button>
                            <button type="button" onclick="setType('UPCYCLE')" id="btn_UPCYCLE" class="type-btn px-5 py-2.5 border border-gray-300 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-50">Produk Upcycle</button>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Judul listing</label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="Sepeda Lipat Bekas" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border">
                    </div>

                    <!-- Etalase (Hanya Upcycle) -->
                    <div id="field_storefront" class="mb-6 hidden">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Etalase</label>
                        <select name="storefront_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border">
                            <option value="">Pilih Etalase</option>
                            @foreach($storefronts ?? [] as $storefront)
                                <option value="{{ $storefront->id }}" {{ old('storefront_id') == $storefront->id ? 'selected' : '' }}>{{ $storefront->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Wajib untuk Produk Upcycle. Produk tampil di etalase hanya setelah disetujui hub.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <!-- Kategori (Preloved & Upcycle) -->
                        <div id="field_category">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                            <select name="category_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border text-gray-700">
                                <option value="">Olahraga</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Jenis Material (Hanya Recyclable) -->
                        <div id="field_material" class="hidden">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Jenis material</label>
                            <select name="material_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border text-gray-700">
                                <option value="">Pilih Material</option>
                                @foreach($materials ?? [] as $material)
                                    <option value="{{ $material->id }}" {{ old('material_id') == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kondisi (Semua Tipe) -->
                        <div id="field_condition">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Kondisi</label>
                            <select name="condition" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border text-gray-700">
                                <option value="Baik" {{ old('condition') == 'Baik' ? 'selected' : '' }}>Baik</option>
                                <option value="Baru (buatan tangan)" {{ old('condition') == 'Baru (buatan tangan)' ? 'selected' : '' }}>Baru (buatan tangan)</option>
                                <option value="Layak Pakai" {{ old('condition') == 'Layak Pakai' ? 'selected' : '' }}>Layak Pakai</option>
                            </select>
                        </div>

                        <!-- Berat (Hanya Recyclable & Upcycle) -->
                        <div id="field_weight" class="hidden">
                            <label id="label_weight" class="block text-sm font-bold text-gray-700 mb-2">Berat per lot (kg)</label>
                            <input type="number" step="0.1" name="weight_per_lot" value="{{ old('weight_per_lot') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border">
                            <p id="desc_weight" class="text-xs text-gray-500 mt-1"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label id="label_price" class="block text-sm font-bold text-gray-700 mb-2">Harga (Rp)</label>
                            <input type="number" name="price" value="{{ old('price') }}" placeholder="850000" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border">
                        </div>
                        <div>
                            <label id="label_stock" class="block text-sm font-bold text-gray-700 mb-2">Stok</label>
                            <input type="number" name="stock" value="{{ old('stock') }}" placeholder="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Hub</label>
                            <select name="hub_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border text-gray-700">
                                <option value="">Bank Sampah Cicendo</option>
                                @foreach($hubs ?? [] as $hub)
                                    <option value="{{ $hub->id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Wilayah</label>
                            <select name="region_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-2.5 border text-gray-700">
                                <option value="">Bandung Kota</option>
                                @foreach($regions ?? [] as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                        <textarea name="description" rows="4" placeholder="Sepeda lipat 16 inci, rem dan rantai normal, ada lecet kecil di rangka." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 p-3 border text-gray-700">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-2">
                        <label class="block text-sm font-bold text-gray-700 mb-4">Foto (2 dari 5)</label>
                        <!-- Kotak Foto Placeholder (Sesuai UI, ini untuk visualisasi saja. Idealnya diganti plugin upload yang bagus) -->
                        <div class="flex space-x-4 mb-4">
                            <div class="w-24 h-24 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400 font-medium">Foto 1</div>
                            <div class="w-24 h-24 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400 font-medium">Foto 2</div>
                            
                            <!-- Input File Asli -->
                            <div class="relative w-24 h-24 border-2 border-dashed border-gray-300 rounded flex flex-col items-center justify-center text-gray-500 hover:bg-gray-50 cursor-pointer overflow-hidden">
                                <span class="text-sm font-bold">+ Foto</span>
                                <input type="file" name="images[]" multiple accept="image/png, image/jpeg" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">JPEG/PNG, maks 2 MB. Metadata lokasi dihapus otomatis.</p>
                    </div>
                </div>

                <!-- KOLOM KANAN: Panduan & Tombol Aksi -->
                <div class="md:col-span-1">
                    <!-- Card pembungkus Panduan dan Tombol -->
                    <div class="bg-white p-6 rounded-xl border border-gray-200 h-fit">
                        
                        <!-- Kotak Panduan Dinamis -->
                        <div id="guide_card" class="bg-green-50 p-5 rounded-lg mb-6">
                            <h3 id="guide_title" class="text-green-800 font-bold mb-2">Panduan: Barang Bekas</h3>
                            <p id="guide_text" class="text-sm text-green-800 leading-relaxed">
                                Jelaskan kondisi apa adanya, termasuk cacat. Foto yang jelas mempercepat verifikasi hub.
                            </p>
                        </div>
                        
                        <!-- Tombol Aksi -->
                        <div class="flex flex-col space-y-3">
                            <button type="submit" name="action" value="submit" class="w-full bg-green-700 text-white font-bold py-2.5 px-4 rounded-md hover:bg-green-800 transition">
                                Ajukan ke Hub
                            </button>
                            <button type="submit" name="action" value="draft" class="w-full bg-white border border-gray-300 text-gray-800 font-bold py-2.5 px-4 rounded-md hover:bg-gray-50 transition">
                                Simpan sebagai Draf
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- JavaScript untuk Logic Tampilan Dinamis -->
    <script>
        function setType(type) {
            document.getElementById('type_input').value = type;

            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.className = 'type-btn px-5 py-2.5 border border-gray-300 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-50';
            });
            
            document.getElementById('btn_' + type).className = 'type-btn px-5 py-2.5 bg-green-700 text-white rounded-full text-sm font-semibold';

            const fieldCategory = document.getElementById('field_category');
            const fieldMaterial = document.getElementById('field_material');
            const fieldStorefront = document.getElementById('field_storefront');
            const fieldCondition = document.getElementById('field_condition');
            const fieldWeight = document.getElementById('field_weight');
            
            const labelPrice = document.getElementById('label_price');
            const labelStock = document.getElementById('label_stock');
            const labelWeight = document.getElementById('label_weight');
            const descWeight = document.getElementById('desc_weight');

            const guideCard = document.getElementById('guide_card');
            const guideTitle = document.getElementById('guide_title');
            const guideText = document.getElementById('guide_text');

            fieldCategory.classList.add('hidden');
            fieldMaterial.classList.add('hidden');
            fieldStorefront.classList.add('hidden');
            fieldWeight.classList.add('hidden');

            if (type === 'PRELOVED') {
                fieldCategory.classList.remove('hidden');
                
                labelPrice.innerText = 'Harga (Rp)';
                labelStock.innerText = 'Stok';
                
                guideCard.className = 'bg-green-50 p-5 rounded-lg mb-6';
                guideTitle.className = 'text-green-800 font-bold mb-2';
                guideText.className = 'text-sm text-green-800 leading-relaxed';
                guideTitle.innerText = 'Panduan: Barang Bekas';
                guideText.innerText = 'Jelaskan kondisi apa adanya, termasuk cacat. Foto yang jelas mempercepat verifikasi hub.';
            } 
            else if (type === 'RECYCLABLE') {
                fieldMaterial.classList.remove('hidden');
                fieldWeight.classList.remove('hidden');
                
                labelPrice.innerText = 'Harga per lot (Rp)';
                labelStock.innerText = 'Stok (lot)';
                labelWeight.innerText = 'Berat per lot (kg)';
                descWeight.innerText = 'Wajib untuk tipe Material Daur Ulang';
                
                guideCard.className = 'bg-green-50 p-5 rounded-lg mb-6';
                guideTitle.className = 'text-green-800 font-bold mb-2';
                guideText.className = 'text-sm text-green-800 leading-relaxed';
                guideTitle.innerText = 'Panduan: Material Daur Ulang';
                guideText.innerText = 'Jual per lot dan cantumkan berat. Harga acuan hanya referensi, kamu bebas menentukan harga. Listing akan ditinjau pengelola hub sebelum tayang.';
            } 
            else if (type === 'UPCYCLE') {
                fieldStorefront.classList.remove('hidden');
                fieldCategory.classList.remove('hidden');
                fieldWeight.classList.remove('hidden');
                
                labelPrice.innerText = 'Harga (Rp)';
                labelStock.innerText = 'Stok (item)';
                labelWeight.innerText = 'Berat per item (kg), opsional';
                descWeight.innerText = 'Opsional untuk Produk Upcycle. Jika diisi, dicatat sebagai estimasi pada catatan aktivitas.';
                
                guideCard.className = 'bg-yellow-50 p-5 rounded-lg mb-6';
                guideTitle.className = 'text-yellow-800 font-bold mb-2';
                guideText.className = 'text-sm text-yellow-800 leading-relaxed';
                guideTitle.innerText = 'Panduan: Produk Upcycle';
                guideText.innerText = 'Sebutkan bahan daur ulang yang dipakai dan cara pembuatannya. Produk upcycle dipisahkan dari klaim material daur ulang agar pembeli tidak keliru.';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setType(document.getElementById('type_input').value);
        });
    </script>
</x-app-layout>