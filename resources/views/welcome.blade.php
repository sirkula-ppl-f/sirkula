@php
    use App\Enums\ListingType;

    $pillars = [
        [
            'type' => ListingType::Preloved,
            'badge_bg' => 'bg-[#e3f0ff] text-[#1f52c7]',
            'photo_bg' => 'bg-[#edf2fc]',
            'description' => 'Perpanjang usia pakai perabot, buku, elektronik, dan perlengkapan harian layak pakai dari warga sekitar.',
            'example_title' => 'Sepeda Lipat Bekas',
            'example_price' => 'Rp850.000',
            'example_meta' => 'Kondisi baik · Hub Bank Sampah Cicendo',
            'dusk' => 'pillar-preloved',
        ],
        [
            'type' => ListingType::Recyclable,
            'badge_bg' => 'bg-[#e0f5e8] text-[#127345]',
            'photo_bg' => 'bg-[#ebf7f0]',
            'description' => 'Salurkan plastik PET, kardus, kaca, dan kaleng aluminium terpilah siap olah dalam satuan lot maupun kilogram.',
            'example_title' => 'Botol PET Terpilah (lot 25 kg)',
            'example_price' => 'Rp112.500 / lot',
            'example_meta' => 'Acuan Rp4.500/kg · Hub RW 05 Dago',
            'dusk' => 'pillar-recyclable',
        ],
        [
            'type' => ListingType::Upcycle,
            'badge_bg' => 'bg-[#ffedd6] text-[#bf610a]',
            'photo_bg' => 'bg-[#fcf2e6]',
            'description' => 'Temukan produk kreatif bernilai tambah hasil daur ulang komunitas dan etalase UMKM pengrajin lokal.',
            'example_title' => 'Tas dari Banner Bekas',
            'example_price' => 'Rp135.000',
            'example_meta' => 'Etalase Kriya Lembang',
            'dusk' => 'pillar-upcycle',
        ],
    ];

    $showcaseListings = [
        [
            'type' => ListingType::Preloved,
            'badge' => ListingType::Preloved->label(),
            'badge_bg' => 'bg-[#e3f0ff] text-[#1f52c7]',
            'photo_bg' => 'bg-[#edf2fc]',
            'title' => 'Sepeda Lipat Bekas',
            'price' => 'Rp850.000',
            'meta' => 'Kondisi baik · Hub Bank Sampah Cicendo',
        ],
        [
            'type' => ListingType::Recyclable,
            'badge' => ListingType::Recyclable->label(),
            'badge_bg' => 'bg-[#e0f5e8] text-[#127345]',
            'photo_bg' => 'bg-[#ebf7f0]',
            'title' => 'Botol PET Terpilah (lot 25 kg)',
            'price' => 'Rp112.500 / lot',
            'meta' => 'Acuan Rp4.500/kg · Hub RW 05 Dago',
        ],
        [
            'type' => ListingType::Upcycle,
            'badge' => ListingType::Upcycle->label(),
            'badge_bg' => 'bg-[#ffedd6] text-[#bf610a]',
            'photo_bg' => 'bg-[#fcf2e6]',
            'title' => 'Tas dari Banner Bekas',
            'price' => 'Rp135.000',
            'meta' => 'Etalase Kriya Lembang',
        ],
        [
            'type' => ListingType::Preloved,
            'badge' => ListingType::Preloved->label(),
            'badge_bg' => 'bg-[#e3f0ff] text-[#1f52c7]',
            'photo_bg' => 'bg-[#edf2fc]',
            'title' => 'Rak Buku Kayu 4 Susun',
            'price' => 'Rp275.000',
            'meta' => 'Kondisi baik · Hub Komunitas Buah Batu',
        ],
    ];

    $modules = [
        [
            'title' => 'Hub Komunitas',
            'status' => 'Titik Verifikasi & Penyaluran',
            'description' => 'Warga dapat bergabung dengan Hub Komunitas terdekat untuk memverifikasi kondisi barang bekas dan menyalurkan material daur ulang terpilah.',
        ],
        [
            'title' => 'Harga Acuan Material',
            'status' => 'Transparansi Nilai Material',
            'description' => 'Acuan harga per kilogram untuk plastik PET, kardus, kaca, dan logam membantu penjual serta pengumpul menentukan harga lot yang wajar.',
        ],
        [
            'title' => 'Etalase UMKM & Panduan',
            'status' => 'Edukasi & Kreasi Upcycle',
            'description' => 'Panduan pemilahan material dari rumah serta etalase khusus bagi pengrajin lokal untuk memasarkan produk hasil upcycle.',
        ],
    ];
@endphp

<x-layouts.app>
    <div class="space-y-12 sm:space-y-16">
        {{-- Bagian 1: Hero Section --}}
        <section
            aria-labelledby="hero-heading"
            class="rounded-2xl border border-[#e3e8e5] bg-white p-6 shadow-xs sm:p-10 lg:p-12"
            dusk="landing-hero"
        >
            <div class="max-w-3xl">
                <span class="inline-flex items-center rounded-full bg-[#e0f5e8] px-3.5 py-1 text-xs font-semibold text-[#127345]">
                    Pasar Sirkular Berbasis Komunitas Kota &amp; Desa
                </span>

                <h1
                    id="hero-heading"
                    class="mt-4 text-2xl font-bold tracking-tight text-[#171c1a] sm:text-4xl"
                >
                    Ubah Barang Bekas &amp; Material Daur Ulang Menjadi Nilai Baru
                </h1>

                <p class="mt-4 text-sm leading-relaxed text-[#6b7570] sm:text-base">
                    Barang bekas layak pakai, material daur ulang terpilah, dan produk upcycle dari jejaring warga serta Hub Komunitas di berbagai wilayah Indonesia.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <a
                        href="#pratinjau-katalog"
                        dusk="hero-catalog-cta"
                        class="inline-flex items-center justify-center rounded-lg bg-[#178c5c] px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800"
                    >
                        Jelajahi Katalog
                    </a>

                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            dusk="hero-dashboard-cta"
                            class="inline-flex items-center justify-center rounded-lg border border-[#e3e8e5] bg-white px-5 py-3 text-sm font-semibold text-[#171c1a] transition hover:bg-gray-50"
                        >
                            Buka Beranda
                        </a>
                    @else
                        <a
                            href="{{ route('register') }}"
                            dusk="hero-register-cta"
                            class="inline-flex items-center justify-center rounded-lg border border-[#178c5c] bg-white px-5 py-3 text-sm font-semibold text-[#178c5c] transition hover:bg-brand-50"
                        >
                            Daftar Sebagai Warga
                        </a>

                        <a
                            href="{{ route('login') }}"
                            dusk="hero-login-cta"
                            class="inline-flex items-center justify-center rounded-lg border border-[#e3e8e5] bg-white px-5 py-3 text-sm font-semibold text-[#171c1a] transition hover:bg-gray-50"
                        >
                            Masuk
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- Bagian 2: Tiga Kategori Listing Utama (Sesuai Badge & Warna Figma) --}}
        <section id="pilar-sirkula" aria-labelledby="pillars-heading" dusk="landing-pillars">
            <div class="mb-6">
                <h2 id="pillars-heading" class="text-xl font-bold text-[#171c1a] sm:text-2xl">
                    Tiga Kategori Utama di Sirkula
                </h2>
                <p class="mt-1 text-sm text-[#6b7570]">
                    Pilih jenis listing sesuai kebutuhan rumah tangga, pengumpulan material komunitas, maupun kreasi daur ulang.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ($pillars as $pillar)
                    <article class="flex flex-col justify-between rounded-xl border border-[#e3e8e5] bg-white p-5 shadow-2xs">
                        <div>
                            <span class="inline-block rounded-md px-2.5 py-1 text-xs font-semibold {{ $pillar['badge_bg'] }}">
                                {{ $pillar['type']->label() }}
                            </span>

                            <h3 class="mt-4 text-base font-bold text-[#171c1a]">
                                {{ $pillar['type']->label() }}
                            </h3>

                            <p class="mt-2 text-sm leading-relaxed text-[#6b7570]">
                                {{ $pillar['description'] }}
                            </p>
                        </div>

                        <div class="mt-5 border-t border-[#e3e8e5] pt-4">
                            <a
                                href="#pratinjau-katalog"
                                dusk="{{ $pillar['dusk'] }}"
                                data-pillar-filter="{{ $pillar['type']->value }}"
                                class="inline-flex items-center text-xs font-semibold text-[#178c5c] hover:underline"
                            >
                                Lihat contoh {{ strtolower($pillar['type']->label()) }} &rarr;
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Bagian 3: Pratinjau Katalog Sirkula (Sesuai Desain Kartu Figma FR-012) --}}
        <section id="pratinjau-katalog" aria-labelledby="showcase-heading" class="scroll-mt-6" dusk="landing-showcase">
            <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <h2 id="showcase-heading" class="text-xl font-bold text-[#171c1a] sm:text-2xl">
                        Pratinjau Katalog Sirkula
                    </h2>
                    <p class="mt-1 text-sm text-[#6b7570]">
                        Contoh tampilan produk terverifikasi dari Hub Komunitas dan Etalase UMKM pada ekosistem Sirkula.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2" role="group" aria-label="Kategori listing">
                    <button
                        type="button"
                        data-showcase-filter="ALL"
                        dusk="showcase-filter-all"
                        aria-pressed="true"
                        class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-full border border-[#178c5c] bg-[#178c5c] px-4 py-1.5 text-xs font-semibold text-white transition"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        data-showcase-filter="{{ ListingType::Preloved->value }}"
                        dusk="showcase-filter-preloved"
                        aria-pressed="false"
                        class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-full border border-[#e3e8e5] bg-white px-4 py-1.5 text-xs font-semibold text-[#171c1a] transition hover:border-[#178c5c]"
                    >
                        Barang Bekas
                    </button>
                    <button
                        type="button"
                        data-showcase-filter="{{ ListingType::Recyclable->value }}"
                        dusk="showcase-filter-recyclable"
                        aria-pressed="false"
                        class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-full border border-[#e3e8e5] bg-white px-4 py-1.5 text-xs font-semibold text-[#171c1a] transition hover:border-[#178c5c]"
                    >
                        Material Daur Ulang
                    </button>
                    <button
                        type="button"
                        data-showcase-filter="{{ ListingType::Upcycle->value }}"
                        dusk="showcase-filter-upcycle"
                        aria-pressed="false"
                        class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-full border border-[#e3e8e5] bg-white px-4 py-1.5 text-xs font-semibold text-[#171c1a] transition hover:border-[#178c5c]"
                    >
                        Produk Upcycle
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($showcaseListings as $item)
                    <article
                        data-showcase-card="{{ $item['type']->value }}"
                        class="flex flex-col overflow-hidden rounded-xl border border-[#e3e8e5] bg-white"
                    >
                        <div class="flex h-[150px] w-full items-center justify-center {{ $item['photo_bg'] }} text-xs text-[#6b7570]">
                            Foto produk
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-3.5">
                            <div>
                                <span class="inline-block rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $item['badge_bg'] }}">
                                    {{ $item['badge'] }}
                                </span>
                            </div>

                            <h3 class="text-[15px] font-semibold text-[#171c1a]">
                                {{ $item['title'] }}
                            </h3>

                            <p class="text-base font-bold text-[#178c5c]">
                                {{ $item['price'] }}
                            </p>

                            <p class="mt-auto text-xs text-[#6b7570]">
                                {{ $item['meta'] }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Bagian 4: Ekosistem & Modul Sirkula --}}
        <section
            id="ekosistem-sirkula"
            aria-labelledby="ecosystem-heading"
            class="rounded-2xl border border-[#e3e8e5] bg-white p-6 sm:p-8"
            dusk="landing-ecosystem"
        >
            <div class="mb-6">
                <h2 id="ecosystem-heading" class="text-xl font-bold text-[#171c1a] sm:text-2xl">
                    Ekosistem Komunitas Kota &amp; Desa
                </h2>
                <p class="mt-1 text-sm text-[#6b7570]">
                    Fitur terpadu yang menghubungkan warga, pengelola hub, dan pelaku UMKM daur ulang.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ($modules as $mod)
                    <div class="rounded-xl border border-[#e3e8e5] bg-[#f7faf7] p-5">
                        <span class="text-xs font-semibold text-[#178c5c]">
                            {{ $mod['status'] }}
                        </span>
                        <h3 class="mt-1.5 text-base font-bold text-[#171c1a]">
                            {{ $mod['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-[#6b7570]">
                            {{ $mod['description'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Bagian 5: Bottom Call-to-Action --}}
        <section
            aria-labelledby="cta-heading"
            class="rounded-2xl bg-[#178c5c] p-6 text-white sm:p-10"
            dusk="landing-bottom-cta"
        >
            <div class="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                <div class="max-w-2xl">
                    <h2 id="cta-heading" class="text-xl font-bold sm:text-2xl">
                        Siap Berkontribusi dalam Ekonomi Sirkular?
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-brand-50 sm:text-base">
                        Daftarkan akun warga Anda untuk mulai menjual barang bekas layak pakai, menyalurkan material terpilah, atau berbelanja produk sirkular.
                    </p>
                </div>

                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            dusk="bottom-dashboard-cta"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[#178c5c] transition hover:bg-brand-50"
                        >
                            Ke Beranda Warga
                        </a>
                    @else
                        <a
                            href="{{ route('register') }}"
                            dusk="bottom-register-cta"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[#178c5c] transition hover:bg-brand-50"
                        >
                            Buat Akun Gratis
                        </a>

                        <a
                            href="{{ route('login') }}"
                            dusk="bottom-login-cta"
                            class="inline-flex items-center justify-center rounded-lg border border-white/80 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700"
                        >
                            Masuk ke Akun
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterButtons = document.querySelectorAll('[data-showcase-filter]');
            const showcaseCards = document.querySelectorAll('[data-showcase-card]');
            const pillarLinks = document.querySelectorAll('[data-pillar-filter]');

            const activeClasses = ['border-[#178c5c]', 'bg-[#178c5c]', 'text-white'];
            const inactiveClasses = ['border-[#e3e8e5]', 'bg-white', 'text-[#171c1a]', 'hover:border-[#178c5c]'];

            function applyShowcaseFilter(selectedType) {
                filterButtons.forEach((btn) => {
                    const isActive = btn.dataset.showcaseFilter === selectedType;
                    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                    if (isActive) {
                        btn.classList.remove(...inactiveClasses);
                        btn.classList.add(...activeClasses);
                    } else {
                        btn.classList.remove(...activeClasses);
                        btn.classList.add(...inactiveClasses);
                    }
                });

                showcaseCards.forEach((card) => {
                    const match = selectedType === 'ALL' || card.dataset.showcaseCard === selectedType;
                    card.classList.toggle('hidden', !match);
                });
            }

            filterButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    applyShowcaseFilter(btn.dataset.showcaseFilter);
                });
            });

            pillarLinks.forEach((link) => {
                link.addEventListener('click', () => {
                    applyShowcaseFilter(link.dataset.pillarFilter);
                });
            });
        });
    </script>
</x-layouts.app>
