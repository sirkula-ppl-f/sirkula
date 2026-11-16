<x-layouts.app>
    <div class="min-h-screen bg-[#F9FAFB] py-8">
        <div class="max-w-6xl mx-auto px-4">
            <!-- Link Kembali -->
            <a href="{{ route('dashboard') }}" class="text-[#059669] font-medium mb-4 inline-flex items-center hover:underline">
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
                                @foreach($conditions as $kondisi)
                                    <option value="{{ $kondisi->value }}" {{ old('kondisi') === $kondisi->value ? 'selected' : '' }}>{{ $kondisi->label() }}</option>
                                @endforeach
                            </select>
                            @error('kondisi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Harga -->
                        <div>
                            <label for="harga_tampil" class="block text-sm font-bold text-gray-800 mb-2">Harga (Rp)</label>
                            <input type="text" inputmode="numeric" id="harga_tampil" autocomplete="off" placeholder="850.000"
                                value="{{ old('harga') !== null ? number_format((int) old('harga'), 0, ',', '.') : '' }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                            <input type="hidden" name="harga" id="harga" value="{{ old('harga') }}">
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
                        <!-- Wilayah (Di Kiri) -->
                        <div>
                            <label for="wilayah_id" class="block text-sm font-bold text-gray-800 mb-2">Wilayah</label>
                            <select name="wilayah_id" id="wilayah_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">
                                <option value="">Pilih Wilayah</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('wilayah_id') == $region->id ? 'selected' : '' }}>
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('wilayah_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Hub (Di Kanan) - opsinya diisi oleh JavaScript sesuai wilayah -->
                        <div>
                            <label for="hub_id" class="block text-sm font-bold text-gray-800 mb-2">Hub</label>
                            <select name="hub_id" id="hub_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5 disabled:bg-gray-100 disabled:text-gray-500">
                                <option value="">Pilih wilayah dulu</option>
                            </select>
                            @error('hub_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-6">
                        <label for="deskripsi" class="block text-sm font-bold text-gray-800 mb-2">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Sepeda lipat 16 inci, rem dan rantai normal..."
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#059669] focus:ring-[#059669] px-4 py-2.5">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- UPLOAD FOTO -->
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
                        <input type="file" id="fotos" name="fotos[]" multiple accept="image/jpeg,image/png" class="hidden">

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
                            <button type="submit" name="action" value="submit" class="w-full py-2.5 bg-[#059669] text-white font-semibold rounded-lg hover:bg-[#047857] transition shadow-sm">
                                Ajukan ke Hub
                            </button>

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
        document.addEventListener('DOMContentLoaded', function () {

            // =====================================================
            // 1. UPLOAD FOTO (akumulatif, maks 5)
            // =====================================================
            const MAX_FOTO = 5;
            const MAX_SIZE = 2 * 1024 * 1024; // 2 MB
            const fileInput = document.getElementById('fotos');
            const container = document.getElementById('foto-preview-container');
            const btnAdd = document.getElementById('btn-add-foto');
            const countText = document.getElementById('foto-count');
            let selected = [];

            function syncInput() {
                const dt = new DataTransfer();
                selected.forEach(f => dt.items.add(f));
                fileInput.files = dt.files;
            }

            function renderFotos() {
                container.querySelectorAll('.foto-box').forEach(b => b.remove());

                selected.forEach((file, i) => {
                    const box = document.createElement('div');
                    box.className = 'foto-box relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200';

                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.className = 'w-full h-full object-cover';

                    const rm = document.createElement('button');
                    rm.type = 'button';
                    rm.textContent = '×';
                    rm.setAttribute('aria-label', 'Hapus foto');
                    rm.className = 'absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white text-xs leading-5 text-center';
                    rm.addEventListener('click', () => {
                        selected.splice(i, 1);
                        syncInput();
                        renderFotos();
                    });

                    box.append(img, rm);
                    container.insertBefore(box, btnAdd);
                });

                countText.textContent = selected.length;
                btnAdd.style.display = selected.length >= MAX_FOTO ? 'none' : 'flex';
            }

            btnAdd.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                const errors = [];

                Array.from(fileInput.files).forEach(file => {
                    if (selected.length >= MAX_FOTO) return;
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                        errors.push(file.name + ': format harus JPEG/PNG');
                        return;
                    }
                    if (file.size > MAX_SIZE) {
                        errors.push(file.name + ': ukuran lebih dari 2 MB');
                        return;
                    }
                    const duplikat = selected.some(f => f.name === file.name && f.size === file.size);
                    if (!duplikat) selected.push(file);
                });

                syncInput();
                renderFotos();

                if (errors.length) alert(errors.join('\n'));
            });

            // =====================================================
            // 2. FILTER HUB BERDASARKAN WILAYAH
            // =====================================================
            const allHubs = @json($hubs);
            const oldHubId = @json((string) old('hub_id'));

            const wilayahSelect = document.getElementById('wilayah_id');
            const hubSelect = document.getElementById('hub_id');

            function renderHubs() {
                const regionId = wilayahSelect.value;
                hubSelect.innerHTML = '';

                if (!regionId) {
                    hubSelect.add(new Option('Pilih hub', ''));
                    hubSelect.disabled = true;
                    return;
                }

                const filtered = allHubs.filter(h => String(h.region_id) === regionId);

                if (filtered.length === 0) {
                    hubSelect.add(new Option('Belum ada hub di wilayah ini', ''));
                    hubSelect.disabled = true;
                    return;
                }

                hubSelect.disabled = false;
                hubSelect.add(new Option('Pilih Hub', ''));
                filtered.forEach(h => {
                    hubSelect.add(new Option(h.name, h.id, false, String(h.id) === oldHubId));
                });
            }

            wilayahSelect.addEventListener('change', renderHubs);
            renderHubs(); // jalan sekali saat halaman dibuka (menangani old() setelah validasi gagal)

            const hargaTampil = document.getElementById('harga_tampil');
            const hargaAsli = document.getElementById('harga');

            // =====================================================
            // 2. FILTER HARGA
            // =====================================================


            if (hargaTampil && hargaAsli) {
                hargaTampil.addEventListener('input', () => {
                    const angka = hargaTampil.value.replace(/\D/g, '');
                    hargaAsli.value = angka;
                    hargaTampil.value = angka ? Number(angka).toLocaleString('id-ID') : '';
                });
            }
        });
    </script>
</x-layouts.app>
