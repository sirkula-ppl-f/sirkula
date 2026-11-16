<x-layouts.app>
    <div class="min-h-screen bg-[#F9FAFB] py-8">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Link Kembali -->
            <a href="#" class="text-[#059669] font-medium mb-4 inline-flex items-center hover:underline">
                &larr; Kembali ke Listing Saya
            </a>

            <h1 class="text-3xl font-bold text-gray-900 mb-8">Buat Listing Baru</h1>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('listings.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row gap-6">
                @csrf

                <!-- KARTU KIRI: FORM UTAMA -->
                <div class="flex-1 bg-white p-8 rounded-xl shadow-sm border border-gray-200">

                    <!-- Tipe Listing -->
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-800 mb-3">Tipe listing</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="cursor-pointer">
                                 <input type="radio" name="tipe" value="PRELOVED" class="peer sr-only" checked>
                                 <div class="px-5 py-2 rounded-full border border-gray-300 text-gray-700 peer-checked:border-[#059669] peer-checked:bg-[#059669] peer-checked:text-white font-medium text-sm transition-colors">
                                     Barang Bekas
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="RECYCLABLE" class="peer sr-only">
                                <div class="px-5 py-2 rounded-full border border-gray-300 text-gray-700 peer-checked:border-[#059669] peer-checked:bg-[#059669] peer-checked:text-white font-medium text-sm transition-colors">
                                    Material Daur Ulang
                                </div>
                            </label>
                           <label class="cursor-pointer">
                                <input type="radio" name="tipe" value="UPCYCLE" class="peer sr-only">
                                <div class="px-5 py-2 rounded-full border border-gray-300 text-gray-700 peer-checked:border-[#059669] peer-checked:bg-[#059669] peer-checked:text-white font-medium text-sm transition-colors">
                                    Produk Upcycle
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Judul Listing -->
                    <div class="mb-6">
                        <label for="judul" class="block text-sm font-bold text-gray-800 mb-2">Judul listing</label>
                        <input type="text" name="judul" id="judul" value="{{ old('judul') }}" placeholder="Sepeda Lipat Bekas"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                        @error('judul') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Kategori -->
                        <div>
                            <label for="kategori_id" class="block text-sm font-bold text-gray-800 mb-2">Kategori</label>
                            <select name="kategori_id" id="kategori_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $kategori)
                                    <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kategori_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Kondisi -->
                        <div>
                            <label for="kondisi" class="block text-sm font-bold text-gray-800 mb-2">Kondisi</label>
                            <select name="kondisi" id="kondisi" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                                <option value="">Pilih Kondisi</option>
                                <option value="Seperti Baru" {{ old('kondisi') == 'Seperti Baru' ? 'selected' : '' }}>Seperti Baru</option>
                                <option value="Baik" {{ old('kondisi') == 'Baik' ? 'selected' : '' }}>Baik</option>
                                <option value="Cukup" {{ old('kondisi') == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                                <option value="Kurang" {{ old('kondisi') == 'Kurang' ? 'selected' : '' }}>Kurang</option>
                            </select>
                            @error('kondisi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Harga -->
                        <div>
                            <label for="harga" class="block text-sm font-bold text-gray-800 mb-2">Harga (Rp)</label>
                            <input type="number" name="harga" id="harga" value="{{ old('harga') }}" min="0" placeholder="850000"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                            @error('harga') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Stok -->
                        <div>
                            <label for="stok" class="block text-sm font-bold text-gray-800 mb-2">Stok</label>
                            <input type="number" name="stok" id="stok" value="{{ old('stok', 1) }}" min="1"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                            @error('stok') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Hub -->
                        <div>
                            <label for="hub_id" class="block text-sm font-bold text-gray-800 mb-2">Hub</label>
                            <select name="hub_id" id="hub_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                                <option value="">Pilih Hub</option>
                                @foreach($hubs as $hub)
                                    <!-- Sisipkan data-region di sini -->
                                    <option value="{{ $hub->id }}" data-region="{{ $hub->region_id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>
                                        {{ $hub->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('hub_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Wilayah (Otomatis & Terkunci) -->
                        <div>
                            <label for="wilayah_id" class="block text-sm font-bold text-gray-800 mb-2">Wilayah</label>
                            <!-- Tambahkan bg-gray-100 dan disabled -->
                            <select name="wilayah_id" id="wilayah_id" disabled class="w-full bg-gray-100 text-gray-500 rounded-lg border-gray-300 shadow-sm px-4 py-2.5 cursor-not-allowed">
                                <option value="">Pilih Wilayah</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-6">
                        <label for="deskripsi" class="block text-sm font-bold text-gray-800 mb-2">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Sepeda lipat 16 inci, rem dan rantai normal..."
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- UPLOAD FOTO (UI SESUAI MOCKUP) -->
                    <div class="mb-2">
                        <label class="block text-sm font-bold text-gray-800 mb-3">Foto (<span id="foto-count">0</span> dari 5)</label>

                        <!-- Container Kotak Foto -->
                        <div class="flex flex-wrap gap-4" id="foto-preview-container">
                            <!-- Tombol + Foto -->
                            <button type="button" id="btn-add-foto" class="w-24 h-24 border-2 border-dashed border-gray-400 rounded-lg flex items-center justify-center text-gray-500 font-medium hover:bg-gray-50 transition-colors">
                                + Foto
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">JPEG/PNG, maks 2 MB. Metadata lokasi dihapus otomatis.</p>

                        <!-- Input File Asli (Disembunyikan) -->
                        <input type="file" id="fotos" name="fotos[]" multiple accept="image/jpeg, image/png" class="hidden">

                        @error('fotos') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        @error('fotos.*') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- KARTU KANAN: PANDUAN & TOMBOL SUBMIT -->
                <div class="w-full lg:w-[340px]">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <!-- Panduan -->
                        <div class="bg-[#eefcf4] p-5 rounded-lg border border-[#c1f0d3] mb-6">
                            <h3 class="text-[#059669] font-bold mb-2">Panduan: Barang Bekas</h3>
                            <p class="text-sm text-[#065f46] leading-relaxed">
                                Jelaskan kondisi apa adanya, termasuk cacat. Foto yang jelas mempercepat verifikasi hub.
                            </p>
                        </div>

                        <!-- Tombol -->
                    <div class="flex flex-col gap-3">
                        <!-- Tombol Ajukan -->
                        <button type="submit" name="action" value="submit" class="w-full py-2.5 bg-[#059669] text-white font-semibold rounded-lg hover:bg-[#047857] transition shadow-sm">
                            Ajukan ke Hub
                        </button>

                        <!-- Tombol Draf (Ubah type jadi submit, beri name dan value) -->
                        <button type="submit" name="action" value="draft" class="w-full py-2.5 bg-white text-gray-800 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition shadow-sm">
                            Simpan sebagai Draf
                        </button>
                    </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            //  SCRIPT UPLOAD FOTO
            const fileInput = document.getElementById('fotos');
            const container = document.getElementById('foto-preview-container');
            const btnAdd = document.getElementById('btn-add-foto');
            const countText = document.getElementById('foto-count');

            btnAdd.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                const files = Array.from(this.files).slice(0, 5);
                container.querySelectorAll('.foto-box').forEach(box => box.remove());

                files.forEach((file, index) => {
                    const box = document.createElement('div');
                    box.className = 'foto-box w-24 h-24 bg-[#eef2f6] rounded-lg flex items-center justify-center text-[#64748b] text-sm font-medium';
                    box.textContent = `Foto ${index + 1}`;
                    container.insertBefore(box, btnAdd);
                });

                countText.textContent = files.length;
                if(files.length >= 5) {
                    btnAdd.style.display = 'none';
                } else {
                    btnAdd.style.display = 'flex';
                }
            });

            // OTOMATISASI WILAYAH
            // --- 2. SCRIPT OTOMATISASI WILAYAH ---
            const hubSelect = document.getElementById('hub_id');
            const wilayahSelect = document.getElementById('wilayah_id');

            if (hubSelect && wilayahSelect) {
                // Buat fungsi terpisah agar bisa dipanggil berkali-kali
                const updateWilayah = function() {
                    const selectedOption = hubSelect.options[hubSelect.selectedIndex];
                    // Tambahkan pengecekan if(selectedOption) agar tidak error
                    if(selectedOption) {
                        const regionId = selectedOption.getAttribute('data-region');
                        wilayahSelect.value = regionId ? regionId : '';
                    }
                };

                // 1. Jalankan saat user mengganti pilihan Hub
                hubSelect.addEventListener('change', updateWilayah);

                // 2. Jalankan saat halaman pertama kali dimuat
                // (Berguna jika ada old value dari Laravel setelah form error)
                updateWilayah();
            }

        });
    </script>
</x-layouts.app>
