<x-layouts.app title="Edit Profil Publik">
    <div class="max-w-7xl w-full mx-auto px-6 lg:px-12 pt-8 pb-16">
        
        <!-- Back Navigation Link -->
        <div class="mb-4">
            <a class="inline-flex items-center text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors group" href="{{ route('dashboard') }}">
               <span class="mr-1.5 transition-transform group-hover:-translate-x-1">←</span>
               Kembali ke Beranda
            </a>
        </div>

        <!-- Page Title -->
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-8">
            Edit Profil Publik
        </h1>

        {{-- Notifikasi Sukses --}}
        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative shadow-sm" role="alert">
               <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Two Column Layout: Edit Form & Live Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Edit Form Card -->
            <section class="lg:col-span-8 bg-white rounded-2xl border border-gray-200/80 p-8 shadow-sm" data-purpose="edit-profile-form">
                <form action="{{ route('profil.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <!-- Field 1: Nama Tampilan -->
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-900 mb-2" for="nama_tampilan">
                            Nama tampilan
                        </label>
                        <input class="w-full rounded-xl border {{ $errors->has('name') ? 'border-red-500' : 'border-gray-300' }} px-4 py-3 text-base text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent transition" 
                               id="nama_tampilan" 
                               name="name" 
                               type="text" 
                               value="{{ old('name', $user->name) }}" 
                               required 
                               aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                               @error('name') aria-describedby="name-error" @enderror />
                        @error('name') 
                            <span id="name-error" class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Field 2: Wilayah Dropdown -->
                    <div class="mb-2">
                        <label class="block text-sm font-bold text-gray-900 mb-2" for="wilayah">
                            Wilayah
                        </label>
                        <div class="relative">
                            <select class="w-full appearance-none rounded-xl border {{ $errors->has('region_id') ? 'border-red-500' : 'border-gray-300' }} px-4 py-3 text-base text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent transition pr-10 cursor-pointer" 
                                    id="wilayah" 
                                    name="region_id" 
                                    required
                                    aria-invalid="{{ $errors->has('region_id') ? 'true' : 'false' }}"
                                    @error('region_id') aria-describedby="region-error" @enderror>
                                <option value="">Pilih Wilayah</option>
                                
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ (old('region_id', $user->region_id ?? '') == $region->id) ? 'selected' : '' }}>
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Dropdown Arrow Indicator -->
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 text-xs">
                                ▾
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1.5 font-normal">
                            Dipilih dari data master wilayah
                        </p>
                        @error('region_id') 
                            <span id="region-error" class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Privacy Footnote -->
                    <div class="mt-5 mb-8">
                        <p class="text-xs text-gray-500 font-normal">
                            Email, nomor telepon, dan alamat rinci tidak ditampilkan di profil publik.
                        </p>
                    </div>

                    <!-- Action Buttons Group -->
                    <div class="flex items-center space-x-3.5 pt-1">
                        <button class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-150 ease-in-out shadow-sm" type="submit">
                            Simpan perubahan
                        </button>
                        <a href="{{ route('dashboard') }}" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-semibold px-6 py-3 rounded-xl transition duration-150 ease-in-out text-center">
                            Batal
                        </a>
                    </div>
                </form>
            </section>

            <!-- Right Column: Public Preview Card -->
            <aside class="lg:col-span-4 bg-white rounded-2xl border border-gray-200/80 p-8 shadow-sm flex flex-col items-center" data-purpose="public-preview-card">
               <!-- Preview Label -->
               <span class="text-xs font-semibold text-gray-500 tracking-wide mb-6">
                   Tampilan publik
               </span>
               
               <!-- Avatar Preview -->
               <div aria-hidden="true" class="w-24 h-24 rounded-full bg-brand-100 flex-shrink-0 mb-4 flex items-center justify-center text-brand-700 font-bold text-2xl">
                    {{ substr($user->name ?? 'D', 0, 1) }}
               </div>
               
               <!-- Live User Info Preview -->
               <h2 class="text-xl font-bold text-gray-900 text-center tracking-tight" id="preview_name">
                   {{ $user->name }}
               </h2>
               <p class="text-sm font-normal text-gray-500 text-center mt-1" id="preview_city">
                   {{ $user->region->name ?? 'Belum memilih wilayah' }}
               </p>
            </aside>
        </div>
    </div>

    <!-- Live Preview Sync Script -->
    <script>
        const nameInput = document.getElementById('nama_tampilan');
        const regionSelect = document.getElementById('wilayah');
        const previewName = document.getElementById('preview_name');
        const previewCity = document.getElementById('preview_city');

        if (nameInput && previewName) {
           nameInput.addEventListener('input', (e) => {
               previewName.textContent = e.target.value.trim() || 'Nama tampilan';
           });
        }

        if (regionSelect && previewCity) {
           regionSelect.addEventListener('change', (e) => {
                const selectedText = e.target.options[e.target.selectedIndex].text;
               previewCity.textContent = e.target.value ? selectedText : 'Belum memilih wilayah';
           });
        }
    </script>
</x-layouts.app>
