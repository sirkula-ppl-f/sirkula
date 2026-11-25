<?php

namespace Tests\Browser;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\Region;
use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * PBI-06 / FR-020, FR-021. Admin menonaktifkan dan memulihkan listing lewat dialog konfirmasi,
 * katalog publik ikut berubah, riwayat moderasi tercatat, serta tampilan di lebar 360px termasuk
 * menu seluler admin.
 *
 * Elemen dicari lewat atribut dusk, bukan teks, karena teks seperti "Dinonaktifkan admin" muncul
 * di badge status sekaligus di daftar alasan tidak tayang.
 */
class ListingModerationTest extends DuskTestCase
{
    use DatabaseTruncation;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Moderasi']);
    }

    /**
     * Tanda di halaman saat ini. Hilang bila form ternyata terkirim dan halaman berpindah.
     */
    private function tandaiHalaman(Browser $browser): void
    {
        $browser->script('window.halamanAwal = true;');
    }

    private function duskAktif(Browser $browser): ?string
    {
        return $browser->script('return document.activeElement.getAttribute("dusk");')[0];
    }

    private function jumlahBarisRiwayat(Browser $browser): int
    {
        return $browser->script('return document.querySelectorAll(\'[dusk^="listing-history-row-"]\').length;')[0];
    }

    private function barisRiwayatTeratas(Browser $browser): ?string
    {
        return $browser->script('return document.querySelector(\'[dusk^="listing-history-row-"]\')?.getAttribute("dusk") ?? null;')[0];
    }

    /**
     * Jendela Chrome tidak bisa diperkecil di bawah ~500px, jadi lebar 360px diemulasikan lewat
     * DevTools (pola yang sama dengan ReferencePriceTest).
     */
    private function emulasi360(Browser $browser, callable $langkah): void
    {
        $devTools = new ChromeDevToolsDriver($browser->driver);
        $devTools->execute('Emulation.setDeviceMetricsOverride', [
            'width' => 360,
            'height' => 800,
            'deviceScaleFactor' => 1,
            'mobile' => true,
        ]);

        try {
            $langkah();
        } finally {
            $devTools->execute('Emulation.clearDeviceMetricsOverride');
        }
    }

    private function assertTanpaGeserHorizontal(Browser $browser, string $halaman): void
    {
        [$scrollWidth, $clientWidth, $innerWidth] = $browser->script(
            'return [document.documentElement.scrollWidth, document.documentElement.clientWidth, window.innerWidth];'
        )[0];

        // Di emulasi mobile, innerWidth ikut melebar bila halaman meluber, jadi scrollWidth dicek lebih dulu.
        $this->assertLessThanOrEqual(
            $clientWidth,
            $scrollWidth,
            "Halaman {$halaman} bergeser horizontal ({$scrollWidth}px > {$clientWidth}px)."
        );
        $this->assertLessThanOrEqual(360, $innerWidth, 'Viewport tes harus selebar 360px.');
    }

    /**
     * Elemen tidak terpotong di kiri dan tidak meluber ke kanan layar.
     */
    private function assertDalamLayar(Browser $browser, string $dusk): void
    {
        [$kiri, $kanan, $lebar] = $browser->script(
            'const r = document.querySelector(\'[dusk="'.$dusk.'"]\').getBoundingClientRect();'
            .'return [r.left, r.right, document.documentElement.clientWidth];'
        )[0];

        $this->assertGreaterThanOrEqual(0, $kiri, "Elemen {$dusk} terpotong di kiri.");
        $this->assertLessThanOrEqual($lebar, $kanan, "Elemen {$dusk} meluber ke kanan ({$kanan}px > {$lebar}px).");
    }

    /**
     * Tautan di menu seluler terlihat, mengarah ke $href, dan tidak keluar dari lebar layar.
     */
    private function assertTautanMenuTerlihat(Browser $browser, string $dusk, string $href): void
    {
        $browser->assertVisible('@'.$dusk)->assertAttribute('@'.$dusk, 'href', $href);

        $this->assertDalamLayar($browser, $dusk);
    }

    /**
     * Teks pilihan yang sedang tampil di setiap select dalam form muat (tidak terpotong), dan
     * tidak ada kontrol yang keluar dari form. Lebar teks diukur dengan font select itu sendiri,
     * ditambah padding dan perkiraan 20px untuk panah bawaan browser.
     */
    private function assertFilterTidakTerpotong(Browser $browser, string $form, string $halaman): void
    {
        $hasil = $browser->script(<<<JS
            const form = document.querySelector('[dusk="{$form}"]');
            const batasKanan = form.getBoundingClientRect().right;
            const ctx = document.createElement('canvas').getContext('2d');

            return [...form.querySelectorAll('select, input')].filter((el) => el.type !== 'hidden').map((el) => {
                const cs = getComputedStyle(el);
                let butuh = 0;
                let teks = '';

                if (el.tagName === 'SELECT') {
                    ctx.font = cs.font;
                    teks = el.options[el.selectedIndex].text;
                    butuh = Math.ceil(ctx.measureText(teks).width + parseFloat(cs.paddingLeft) + parseFloat(cs.paddingRight) + 20);
                }

                return [el.name, teks, el.clientWidth, butuh, el.getBoundingClientRect().right <= batasKanan + 0.5];
            });
            JS)[0];

        $this->assertNotEmpty($hasil);

        foreach ($hasil as [$nama, $teks, $lebar, $butuh, $dalamForm]) {
            $this->assertGreaterThanOrEqual($butuh, $lebar, "Filter {$nama} di {$halaman} terpotong: \"{$teks}\" butuh ±{$butuh}px, lebarnya {$lebar}px.");
            $this->assertTrue($dalamForm, "Filter {$nama} di {$halaman} keluar dari form.");
        }
    }

    public function test_admin_menonaktifkan_dan_memulihkan_listing_lalu_katalog_dan_riwayat_ikut_berubah(): void
    {
        $listing = Listing::factory()->create(['title' => 'Lemari Jati Uji Moderasi']);
        $pembanding = Listing::factory()->create(['title' => 'Kursi Rotan Pembanding']);
        $alasanNonaktif = 'Foto listing memakai gambar milik toko lain.';
        $alasanPulih = 'Penjual sudah mengganti foto dengan foto asli.';

        $this->browse(function (Browser $browser) use ($listing, $pembanding, $alasanNonaktif, $alasanPulih) {
            // Kondisi awal: listing tampil di katalog untuk tamu.
            $browser->visit('/katalog')
                ->assertVisible('@catalog-card-'.$listing->id)
                ->assertVisible('@catalog-card-'.$pembanding->id);

            // Admin mencari listing lewat menu Moderasi Listing.
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->click('@nav-admin-listings')
                ->waitForLocation('/admin/listing')
                ->assertAttribute('@nav-admin-listings', 'aria-current', 'page')
                ->assertVisible('@listing-row-'.$pembanding->id)
                ->type('@listing-search', 'Lemari Jati')
                ->click('@listing-filter-button')
                // Path tidak berubah (hanya query string), jadi tunggu tombol Reset yang hanya muncul bila filter terisi.
                ->waitFor('@listing-filter-reset')
                ->assertInputValue('@listing-search', 'Lemari Jati')
                ->assertVisible('@listing-row-'.$listing->id)
                ->assertMissing('@listing-row-'.$pembanding->id)
                ->assertSeeIn('@listing-catalog-'.$listing->id, 'Ya')
                ->click('@listing-detail-link-'.$listing->id)
                ->waitForLocation('/admin/listing/'.$listing->id)
                ->assertSeeIn('@listing-title', 'Lemari Jati Uji Moderasi')
                ->assertSeeIn('@listing-moderation-status', 'Diizinkan tampil')
                ->assertSeeIn('@listing-catalog-status', 'Ya')
                ->assertVisible('@listing-history-empty');

            // Nonaktifkan lewat dialog konfirmasi.
            $browser->type('@listing-reason', $alasanNonaktif)
                ->click('@listing-deactivate-button')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan listing?')
                ->assertSeeIn('@confirm-dialog', 'Listing “Lemari Jati Uji Moderasi” akan langsung hilang dari katalog.')
                ->assertAttribute('@confirm-dialog-confirm', 'data-variant', 'danger')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Listing “Lemari Jati Uji Moderasi” dinonaktifkan dan tidak lagi tampil di katalog.')
                ->assertPathIs('/admin/listing/'.$listing->id)
                ->assertSeeIn('@listing-moderation-status', 'Dinonaktifkan admin')
                ->assertSeeIn('@listing-catalog-status', 'Tidak')
                ->assertSeeIn('@listing-blockers', 'Dinonaktifkan admin.')
                ->assertVisible('@listing-restore-form')
                ->assertMissing('@listing-deactivate-form')
                ->assertInputValue('@listing-reason', '');

            $nonaktif = ListingModeration::query()->sole();
            $this->assertSame(ListingModerationAction::Deactivate, $nonaktif->action);
            $this->assertSame($alasanNonaktif, $nonaktif->reason);
            $this->assertSame($this->admin->id, $nonaktif->admin_id);

            $browser->assertSeeIn('@listing-history-row-'.$nonaktif->id, 'Dinonaktifkan')
                ->assertSeeIn('@listing-history-row-'.$nonaktif->id, $alasanNonaktif)
                ->assertSeeIn('@listing-history-row-'.$nonaktif->id, 'Admin Moderasi');
            $this->assertSame(1, $this->jumlahBarisRiwayat($browser));

            $segar = $listing->fresh();
            $this->assertSame(ListingModerationStatus::Hidden, $segar->moderation_status);
            $this->assertSame(Listing::REVIEW_APPROVED, $segar->review_status);
            $this->assertSame(Listing::PUBLISH_ACTIVE, $segar->publish_status);

            // Tamu: listing hilang dari katalog dan detailnya 404.
            $browser->click('@logout-button')
                ->waitUntilMissing('@logout-button')
                ->visit('/katalog')
                ->assertVisible('@catalog-card-'.$pembanding->id)
                ->assertMissing('@catalog-card-'.$listing->id)
                ->assertDontSee('Lemari Jati Uji Moderasi')
                ->visit('/katalog/'.$listing->id)
                ->assertSee('404')
                ->assertMissing('@catalog-detail')
                ->assertDontSee('Lemari Jati Uji Moderasi');

            // Admin memulihkan listing.
            $browser->loginAs($this->admin)
                ->visit('/admin/listing/'.$listing->id)
                ->type('@listing-reason', $alasanPulih)
                ->click('@listing-restore-button')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Pulihkan listing?')
                ->assertAttributeMissing('@confirm-dialog-confirm', 'data-variant')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Listing “Lemari Jati Uji Moderasi” dipulihkan.')
                ->assertMissing('@listing-visibility-note')
                ->assertSeeIn('@listing-moderation-status', 'Diizinkan tampil')
                ->assertSeeIn('@listing-catalog-status', 'Ya')
                ->assertMissing('@listing-blockers')
                ->assertVisible('@listing-deactivate-form');

            $pulih = ListingModeration::query()->where('action', ListingModerationAction::Restore->value)->sole();
            $this->assertSame(2, $this->jumlahBarisRiwayat($browser));
            $this->assertSame('listing-history-row-'.$pulih->id, $this->barisRiwayatTeratas($browser), 'Riwayat terbaru harus di atas.');

            // Riwayat global memuat kedua aksi di atas, bisa difilter, dan menautkan ke detail listing.
            $browser->click('@listing-back')
                ->waitForLocation('/admin/listing')
                ->click('@listing-moderations-link')
                ->waitForLocation('/admin/listing/riwayat-moderasi')
                ->assertSeeIn('@moderation-row-'.$nonaktif->id, 'Dinonaktifkan')
                ->assertSeeIn('@moderation-row-'.$nonaktif->id, 'Lemari Jati Uji Moderasi')
                ->assertSeeIn('@moderation-row-'.$nonaktif->id, $alasanNonaktif)
                ->assertSeeIn('@moderation-row-'.$pulih->id, 'Dipulihkan')
                ->assertSeeIn('@moderation-row-'.$pulih->id, $alasanPulih)
                ->select('@moderation-action-filter', ListingModerationAction::Deactivate->value)
                ->click('@moderation-filter-button')
                ->waitFor('@moderation-filter-reset')
                ->assertSelected('@moderation-action-filter', ListingModerationAction::Deactivate->value)
                ->assertVisible('@moderation-row-'.$nonaktif->id)
                ->assertMissing('@moderation-row-'.$pulih->id)
                ->click('@moderation-listing-link-'.$nonaktif->id)
                ->waitForLocation('/admin/listing/'.$listing->id)
                ->assertSeeIn('@listing-title', 'Lemari Jati Uji Moderasi');

            // Tamu: listing tampil lagi di katalog.
            $browser->click('@logout-button')
                ->waitUntilMissing('@logout-button')
                ->visit('/katalog')
                ->assertVisible('@catalog-card-'.$listing->id)
                ->visit('/katalog/'.$listing->id)
                ->assertVisible('@catalog-detail')
                ->assertSeeIn('@catalog-detail', 'Lemari Jati Uji Moderasi');
        });

        $this->assertSame(ListingModerationStatus::Visible, $listing->fresh()->moderation_status);
        $this->assertDatabaseCount('listing_moderations', 2);
    }

    public function test_memulihkan_listing_menunggu_verifikasi_hub_memberi_catatan_dan_tetap_tidak_tayang(): void
    {
        $listing = Listing::factory()->dinonaktifkanAdmin()->create([
            'title' => 'Meja Lipat Menunggu Hub',
            'review_status' => Listing::REVIEW_PENDING,
        ]);
        $pembanding = Listing::factory()->create(['title' => 'Kursi Rotan Pembanding']);

        $this->browse(function (Browser $browser) use ($listing, $pembanding) {
            $browser->loginAs($this->admin)
                ->visit('/admin/listing/'.$listing->id)
                ->assertSeeIn('@listing-review-status', 'Menunggu verifikasi hub')
                ->assertSeeIn('@listing-moderation-status', 'Dinonaktifkan admin')
                ->type('@listing-reason', 'Laporan pelanggaran ternyata tidak terbukti.')
                ->click('@listing-restore-button')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'tetap harus lolos verifikasi hub')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Listing “Meja Lipat Menunggu Hub” dipulihkan.')
                ->assertSeeIn('@listing-visibility-note', 'Listing belum tampil di katalog karena: Menunggu verifikasi hub.')
                ->assertSeeIn('@listing-moderation-status', 'Diizinkan tampil')
                ->assertSeeIn('@listing-review-status', 'Menunggu verifikasi hub')
                ->assertSeeIn('@listing-catalog-status', 'Tidak')
                ->assertSeeIn('@listing-blockers', 'Menunggu verifikasi hub.')
                ->assertDontSeeIn('@listing-blockers', 'Dinonaktifkan admin.');

            $browser->click('@logout-button')
                ->waitUntilMissing('@logout-button')
                ->visit('/katalog')
                ->assertVisible('@catalog-card-'.$pembanding->id)
                ->assertMissing('@catalog-card-'.$listing->id)
                ->assertDontSee('Meja Lipat Menunggu Hub');
        });

        // Pemulihan tidak melewati verifikasi hub.
        $segar = $listing->fresh();
        $this->assertSame(ListingModerationStatus::Visible, $segar->moderation_status);
        $this->assertSame(Listing::REVIEW_PENDING, $segar->review_status);

        $moderasi = ListingModeration::query()->sole();
        $this->assertSame(ListingModerationAction::Restore, $moderasi->action);
        $this->assertSame(Listing::REVIEW_PENDING, $moderasi->review_status_snapshot);
    }

    public function test_batal_di_dialog_konfirmasi_tidak_mengubah_status_dan_riwayat(): void
    {
        $listing = Listing::factory()->create(['title' => 'Rak Sepatu Uji Batal']);
        // Riwayat lama dari factory; factory tidak mengubah status listing.
        ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->create(['created_at' => now()->subDays(2)]);
        ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->restore()->create(['created_at' => now()->subDay()]);
        $alasan = 'Alasan yang tidak jadi dikirim karena dibatalkan.';

        $this->browse(function (Browser $browser) use ($listing, $alasan) {
            $browser->loginAs($this->admin)->visit('/admin/listing/'.$listing->id);
            $this->assertSame(2, $this->jumlahBarisRiwayat($browser));

            $browser->type('@listing-reason', $alasan);
            $this->tandaiHalaman($browser);

            $browser->click('@listing-deactivate-button')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-cancel')
                ->waitUntilMissing('@confirm-dialog')
                ->assertPathIs('/admin/listing/'.$listing->id)
                ->assertAttributeMissing('@listing-deactivate-button', 'disabled')
                ->assertInputValue('@listing-reason', $alasan)
                ->assertSeeIn('@listing-moderation-status', 'Diizinkan tampil')
                ->assertMissing('@alert');

            $this->assertTrue($browser->script('return window.halamanAwal === true;')[0], 'Form tidak boleh terkirim setelah Batal.');
            $this->assertSame('listing-deactivate-button', $this->duskAktif($browser), 'Fokus harus kembali ke tombol Nonaktifkan.');
            $this->assertSame(2, $this->jumlahBarisRiwayat($browser));
        });

        $this->assertSame(ListingModerationStatus::Visible, $listing->fresh()->moderation_status);
        $this->assertDatabaseCount('listing_moderations', 2);
        $this->assertSame(0, AuditLog::query()->where('entity_type', Listing::class)->count());
    }

    public function test_alasan_kosong_menampilkan_error_dan_memindahkan_fokus_ke_alasan(): void
    {
        $listing = Listing::factory()->create(['title' => 'Kipas Angin Uji Validasi']);

        $this->browse(function (Browser $browser) use ($listing) {
            $browser->loginAs($this->admin)
                ->visit('/admin/listing/'.$listing->id)
                ->assertAttributeMissing('@listing-reason', 'aria-invalid')
                ->click('@listing-deactivate-button')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-confirm')
                ->waitForText('Alasan moderasi wajib diisi.')
                ->assertPathIs('/admin/listing/'.$listing->id)
                ->assertAttribute('@listing-reason', 'aria-invalid', 'true')
                ->assertAttribute('@listing-reason', 'aria-describedby', 'reason-hint reason-error')
                ->assertSeeIn('#reason-error', 'Alasan moderasi wajib diisi.')
                ->assertSeeIn('@listing-moderation-status', 'Diizinkan tampil')
                ->assertMissing('@alert');

            $this->assertSame('listing-reason', $this->duskAktif($browser), 'Fokus harus pindah ke kolom alasan.');
            $this->assertSame(0, $this->jumlahBarisRiwayat($browser));
        });

        $this->assertSame(ListingModerationStatus::Visible, $listing->fresh()->moderation_status);
        $this->assertDatabaseCount('listing_moderations', 0);
    }

    public function test_halaman_moderasi_tanpa_geser_horizontal_di_360px(): void
    {
        $provinsi = Region::create(['name' => 'Provinsi Kalimantan Tengah Uji', 'code' => 'KT']);
        $kategori = Category::factory()->create(['name' => 'Perabot Rumah Tangga dan Dapur']);
        $hub = Hub::create([
            'name' => 'Bank Sampah Induk Kecamatan Sukamaju Raya',
            'type' => 'BANK_SAMPAH',
            'region_id' => $provinsi->id,
            'public_address' => 'Jl. Contoh No. 1',
            'status' => 'ACTIVE',
        ]);
        // Judul dan alasan memuat token panjang tanpa spasi untuk menguji teks yang tidak bisa dipatahkan di spasi.
        $judul = str_repeat('Lemarijatiantik', 6);
        $listing = Listing::factory()->create([
            'title' => $judul,
            'category_id' => $kategori->id,
            'hub_id' => $hub->id,
            'region_id' => $provinsi->id,
            'user_id' => User::factory()->create([
                'name' => 'Penjual Dengan Nama Yang Cukup Panjang',
                'email' => 'penjual.dengan.alamat.surel.yang.panjang@contoh.test',
            ])->id,
        ]);
        $url = 'https://contoh.test/'.str_repeat('laporan-pelanggaran-', 9).'bukti.jpg';
        $alasan = 'Foto listing memakai gambar dari internet. Bukti: '.$url.' '.str_repeat('Penjual diminta mengganti foto. ', 7);
        $this->assertGreaterThanOrEqual(190, strlen($url));
        $this->assertLessThanOrEqual(500, mb_strlen($alasan));

        $nonaktif = ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->create([
            'reason' => $alasan,
            'request_id' => str_repeat('a1b2c3d4', 8),
            'created_at' => now()->subDay(),
        ]);
        ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->restore()->create([
            'reason' => $alasan,
            'request_id' => str_repeat('e5f6a7b8', 8),
        ]);

        $filter = http_build_query([
            'cari' => 'Lemarijati',
            'tipe' => 'PRELOVED',
            'kategori' => $kategori->id,
            'hub' => $hub->id,
            'provinsi' => $provinsi->id,
            'verifikasi' => Listing::REVIEW_APPROVED,
            'moderasi' => ListingModerationStatus::Visible->value,
        ]);

        $this->browse(function (Browser $browser) use ($listing, $nonaktif, $filter) {
            $browser->loginAs($this->admin);

            $this->emulasi360($browser, function () use ($browser, $listing, $nonaktif, $filter) {
                // Daftar listing dengan semua filter terisi.
                $browser->visit('/admin/listing?'.$filter)
                    ->assertVisible('@listing-row-'.$listing->id)
                    ->assertVisible('@listing-filter-reset');
                $this->assertTanpaGeserHorizontal($browser, 'daftar listing admin dengan filter');
                $this->assertDalamLayar($browser, 'listing-filter-form');
                $this->assertDalamLayar($browser, 'listing-table-region');

                // Detail listing dengan riwayat, termasuk dialog konfirmasi.
                $browser->visit('/admin/listing/'.$listing->id)
                    ->assertVisible('@listing-history-row-'.$nonaktif->id);
                $this->assertTanpaGeserHorizontal($browser, 'detail listing admin dengan riwayat');
                $this->assertDalamLayar($browser, 'listing-title');
                $this->assertDalamLayar($browser, 'listing-history-region');

                $browser->type('@listing-reason', 'Uji dialog konfirmasi di layar seluler.')
                    ->click('@listing-deactivate-button')
                    ->waitFor('@confirm-dialog');
                $this->assertDalamLayar($browser, 'confirm-dialog');

                [$atas, $bawah, $tinggi] = $browser->script(
                    'const r = document.querySelector(\'[dusk="confirm-dialog"]\').getBoundingClientRect();'
                    .'return [r.top, r.bottom, window.innerHeight];'
                )[0];
                $this->assertGreaterThanOrEqual(0, $atas);
                $this->assertLessThanOrEqual($tinggi, $bawah);

                $browser->click('@confirm-dialog-cancel')->waitUntilMissing('@confirm-dialog');
                $this->assertTanpaGeserHorizontal($browser, 'detail listing setelah dialog ditutup');

                // Riwayat moderasi global dengan filter terisi.
                $browser->visit('/admin/listing/riwayat-moderasi?aksi=DEACTIVATE&cari=Lemarijati')
                    ->assertVisible('@moderation-row-'.$nonaktif->id)
                    ->assertVisible('@moderation-filter-reset');
                $this->assertTanpaGeserHorizontal($browser, 'riwayat moderasi global dengan filter');
                $this->assertDalamLayar($browser, 'moderation-filter-form');
                $this->assertDalamLayar($browser, 'moderation-table-region');
            });
        });
    }

    public function test_menu_seluler_admin_memuat_moderasi_listing(): void
    {
        Listing::factory()->create(['title' => 'Lemari Jati Menu Seluler']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            $this->emulasi360($browser, function () use ($browser) {
                $browser->visit('/admin')
                    ->assertVisible('@nav-toggle')
                    ->assertMissing('@nav-admin-listings')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-toggle', 'aria-expanded', 'true');

                $this->assertTautanMenuTerlihat($browser, 'nav-admin-listings', route('admin.listings.index'));
                $this->assertTanpaGeserHorizontal($browser, 'dashboard admin dengan menu seluler terbuka');

                $browser->click('@nav-admin-listings')
                    ->waitForLocation('/admin/listing')
                    ->assertMissing('@nav-admin-listings')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-toggle', 'aria-expanded', 'true')
                    ->assertAttribute('@nav-admin-listings', 'aria-current', 'page')
                    ->assertSeeIn('@nav-admin-listings', 'Moderasi Listing');

                $this->assertTautanMenuTerlihat($browser, 'nav-admin-listings', route('admin.listings.index'));
                $this->assertDalamLayar($browser, 'nav-admin-listings');

                // Label "Moderasi Listing" utuh, tidak terpotong oleh truncate.
                [$lebarIsi, $lebarTampil] = $browser->script(
                    'const label = document.querySelector(\'[dusk="nav-admin-listings"] span\');'
                    .'return [label.scrollWidth, label.clientWidth];'
                )[0];
                $this->assertLessThanOrEqual($lebarTampil, $lebarIsi, 'Label menu Moderasi Listing terpotong.');

                $this->assertTanpaGeserHorizontal($browser, 'daftar listing admin dengan menu seluler terbuka');
            });
        });
    }

    /**
     * Di layar desktop sidebar admin memakan 256px. Filter tidak boleh terpotong, baik saat form
     * tampil dua kolom (1024px) maupun satu baris lebar (1280px ke atas).
     */
    public function test_filter_tidak_terpotong_di_layar_desktop(): void
    {
        $listing = Listing::factory()->create(['title' => 'Lemari Jati Filter Desktop']);
        ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);
            $devTools = new ChromeDevToolsDriver($browser->driver);

            try {
                foreach ([1024, 1280, 1440] as $lebar) {
                    $devTools->execute('Emulation.setDeviceMetricsOverride', [
                        'width' => $lebar,
                        'height' => 900,
                        'deviceScaleFactor' => 1,
                        'mobile' => false,
                    ]);

                    // Placeholder terpanjang ada di filter status verifikasi dan status moderasi.
                    $browser->visit('/admin/listing')->assertVisible('@listing-filter-form');
                    $this->assertFilterTidakTerpotong($browser, 'listing-filter-form', "daftar listing {$lebar}px");

                    // "Dinonaktifkan" adalah pilihan aksi terpanjang.
                    $browser->visit('/admin/listing/riwayat-moderasi?aksi='.ListingModerationAction::Deactivate->value)
                        ->assertSelected('@moderation-action-filter', ListingModerationAction::Deactivate->value);
                    $this->assertFilterTidakTerpotong($browser, 'moderation-filter-form', "riwayat moderasi {$lebar}px");
                }
            } finally {
                $devTools->execute('Emulation.clearDeviceMetricsOverride');
            }
        });
    }
}
