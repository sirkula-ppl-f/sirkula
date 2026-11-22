<?php

namespace Tests\Browser;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\ReferencePriceService;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * PBI-05 / FR-008. Admin menerbitkan harga acuan (dengan dialog konfirmasi), publik melihat harga
 * terbaru, riwayat, dan grafik tren, serta tampilan di lebar 360px termasuk menu seluler.
 *
 * Tautan navbar dibuktikan lewat atribut dusk dan href, bukan teks, karena "Kelola Harga Acuan"
 * juga memuat teks "Harga Acuan".
 */
class ReferencePriceTest extends DuskTestCase
{
    use DatabaseTruncation;

    private User $admin;

    private Material $plastik;

    private Region $provinsi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Harga']);
        $this->plastik = Material::factory()->create(['name' => 'Plastik PET', 'slug' => 'plastik-pet']);
        $this->provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
    }

    /**
     * Harga yang sudah berlaku $hariLalu hari sebelum hari ini (WIB).
     */
    private function harga(int $hariLalu, int $hargaPerKg, string $sumber = 'Survei harga pengepul'): ReferencePrice
    {
        return ReferencePrice::factory()
            ->forMaterial($this->plastik)
            ->forRegion($this->provinsi)
            ->publishedBy($this->admin)
            ->effectiveOn(app(ReferencePriceService::class)->today()->subDays($hariLalu))
            ->create(['price_per_kg' => $hargaPerKg, 'source' => $sumber]);
    }

    private function isiFormTerbitkan(Browser $browser, string $harga): void
    {
        $browser->select('@material_id', (string) $this->plastik->id)
            ->select('@region_id', (string) $this->provinsi->id)
            ->type('@price_per_kg', $harga)
            ->type('@source', 'Survei harga pengepul');
    }

    /**
     * Tanda di halaman saat ini. Hilang bila form ternyata terkirim dan halaman berpindah.
     */
    private function tandaiHalaman(Browser $browser): void
    {
        $browser->script('window.halamanAwal = true;');
    }

    private function jumlahTitikGrafik(Browser $browser): int
    {
        return $browser->script('return document.querySelectorAll(\'[dusk="trend-chart"] [data-point]\').length;')[0];
    }

    /**
     * Jendela Chrome tidak bisa diperkecil di bawah ~500px, jadi lebar 360px diemulasikan lewat
     * DevTools (pola yang sama dengan MasterDataTest).
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
     * Tautan di menu seluler terlihat, mengarah ke $href, dan tidak keluar dari lebar layar.
     */
    private function assertTautanMenuTerlihat(Browser $browser, string $dusk, string $href): void
    {
        $browser->assertVisible('@'.$dusk)->assertAttribute('@'.$dusk, 'href', $href);

        [$kiri, $kanan, $lebar] = $browser->script(
            'const r = document.querySelector(\'[dusk="'.$dusk.'"]\').getBoundingClientRect();'
            .'return [r.left, r.right, document.documentElement.clientWidth];'
        )[0];

        $this->assertGreaterThanOrEqual(0, $kiri, "Tautan {$dusk} terpotong di kiri.");
        $this->assertLessThanOrEqual($lebar, $kanan, "Tautan {$dusk} meluber ke kanan.");
    }

    public function test_admin_menerbitkan_lalu_tamu_melihat_harga_riwayat_dan_grafik(): void
    {
        $lama = $this->harga(30, 3200);

        $this->browse(function (Browser $browser) use ($lama) {
            $browser->loginAs($this->admin)
                ->visit('/admin')
                ->click('@nav-admin-reference-prices')
                ->waitForLocation('/admin/harga-acuan')
                ->click('@reference-price-create-link')
                ->waitForLocation('/admin/harga-acuan/terbitkan');

            $this->isiFormTerbitkan($browser, '3500');

            $browser->click('@publish-button')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Terbitkan harga acuan?')
                ->click('@confirm-dialog-confirm')
                ->waitForLocation('/admin/harga-acuan')
                ->waitForTextIn('@alert', 'Harga acuan Plastik PET di Provinsi Alfa sebesar Rp 3.500/kg berhasil diterbitkan');

            $baru = ReferencePrice::query()->latest('id')->firstOrFail();
            $this->assertSame(3500, $baru->price_per_kg);
            $this->assertSame($this->admin->id, $baru->published_by);

            $browser->assertSeeIn('@reference-price-row-'.$baru->id, 'Rp 3.500/kg')
                ->assertSeeIn('@reference-price-row-'.$baru->id, 'Berlaku')
                ->assertSeeIn('@reference-price-row-'.$lama->id, 'Riwayat');

            // Keluar, lalu buka halaman publik sebagai tamu.
            $browser->click('@logout-button')
                ->waitUntilMissing('@logout-button')
                ->visit('/harga-acuan')
                ->assertAttribute('@nav-reference-prices', 'href', route('reference-prices.index'))
                ->assertAttribute('@nav-reference-prices', 'aria-current', 'page')
                ->assertMissing('@nav-admin-reference-prices')
                ->assertSeeIn('@reference-price-row-'.$baru->id, 'Rp 3.500/kg')
                ->assertSeeIn('@reference-price-row-'.$baru->id, 'Plastik PET')
                ->assertSeeIn('@reference-price-row-'.$baru->id, 'Provinsi Alfa')
                ->assertDontSee('Admin Harga')
                ->click('@reference-price-history-link-'.$baru->id)
                ->waitForLocation('/harga-acuan/plastik-pet/PA')
                ->assertSeeIn('@reference-price-latest', 'Rp 3.500/kg')
                ->assertSeeIn('@reference-price-latest', 'Survei harga pengepul')
                ->assertVisible('@reference-price-history-table')
                ->assertSeeIn('@reference-price-history-row-'.$baru->id, 'Berlaku')
                ->assertSeeIn('@reference-price-history-row-'.$lama->id, 'Riwayat')
                ->assertSeeIn('@reference-price-history-row-'.$lama->id, 'Rp 3.200/kg')
                ->assertVisible('@trend-chart')
                ->assertDontSee('Admin Harga');

            $this->assertSame(2, $this->jumlahTitikGrafik($browser));

            $browser->click('@reference-price-back')->waitForLocation('/harga-acuan');
        });

        $this->assertDatabaseCount('reference_prices', 2);
    }

    public function test_batal_di_dialog_konfirmasi_tidak_menyimpan_data(): void
    {
        $this->harga(10, 3200);
        $sebelum = ReferencePrice::query()->count();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)->visit('/admin/harga-acuan/terbitkan');

            $this->isiFormTerbitkan($browser, '3500');
            $this->tandaiHalaman($browser);

            $browser->click('@publish-button')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-cancel')
                ->waitUntilMissing('@confirm-dialog')
                ->assertPathIs('/admin/harga-acuan/terbitkan')
                ->assertAttributeMissing('@publish-button', 'disabled')
                ->assertInputValue('@price_per_kg', '3500');

            $this->assertTrue($browser->script('return window.halamanAwal === true;')[0], 'Form tidak boleh terkirim setelah Batal.');
        });

        $this->assertSame($sebelum, ReferencePrice::query()->count());
    }

    public function test_validasi_gagal_menampilkan_error_nilai_lama_dan_harga_yang_berlaku(): void
    {
        $this->harga(10, 3200, 'Rata-rata harga bank sampah');
        $sebelum = ReferencePrice::query()->count();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit('/admin/harga-acuan/terbitkan')
                ->assertMissing('@current-price');

            $this->isiFormTerbitkan($browser, '3.500');

            $browser->click('@publish-button')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-confirm')
                ->waitForText('Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.')
                ->assertPathIs('/admin/harga-acuan/terbitkan')
                ->assertInputValue('@price_per_kg', '3.500')
                ->assertSelected('@material_id', (string) $this->plastik->id)
                ->assertSelected('@region_id', (string) $this->provinsi->id)
                ->assertInputValue('@source', 'Survei harga pengepul')
                ->assertAttribute('@price_per_kg', 'aria-invalid', 'true')
                ->assertVisible('@current-price')
                ->assertSeeIn('@current-price', 'Rp 3.200/kg')
                ->assertSeeIn('@current-price', 'Rata-rata harga bank sampah');
        });

        $this->assertSame($sebelum, ReferencePrice::query()->count());
    }

    public function test_halaman_harga_acuan_tanpa_geser_horizontal_di_360px(): void
    {
        foreach ([180, 120, 90, 60, 30] as $i => $hariLalu) {
            $this->harga($hariLalu, 3000 + $i * 125);
        }
        // Sumber panjang tanpa spasi untuk menguji teks yang tidak bisa dipatahkan di spasi.
        $terbaru = $this->harga(0, 3650, str_repeat('Laporanhargamaterial', 12));

        $this->browse(function (Browser $browser) use ($terbaru) {
            $browser->loginAs($this->admin);

            $this->emulasi360($browser, function () use ($browser, $terbaru) {
                $browser->visit('/admin/harga-acuan')->assertVisible('@reference-price-row-'.$terbaru->id);
                $this->assertTanpaGeserHorizontal($browser, 'riwayat harga acuan admin');

                $browser->visit('/admin/harga-acuan/terbitkan')->assertVisible('@reference-price-form');
                $this->assertTanpaGeserHorizontal($browser, 'terbitkan harga acuan');

                $browser->visit('/harga-acuan')->assertVisible('@reference-price-row-'.$terbaru->id);
                $this->assertTanpaGeserHorizontal($browser, 'daftar harga acuan publik');

                $browser->visit('/harga-acuan/plastik-pet/PA')
                    ->assertVisible('@reference-price-latest')
                    ->assertVisible('@trend-chart')
                    ->assertVisible('@reference-price-history-table');
                $this->assertTanpaGeserHorizontal($browser, 'detail harga acuan');
                $this->assertSame(6, $this->jumlahTitikGrafik($browser));

                // Grafik tidak lebih lebar dari layar, dan teks sumbu terbaca (>= 10px setelah diskalakan).
                [$lebarGrafik, $lebarLayar, $hurufLabel] = $browser->script(
                    'const svg = document.querySelector(\'[dusk="trend-chart"]\');'
                    .'const label = svg.querySelector("[data-price-tick]").getBoundingClientRect();'
                    .'return [svg.getBoundingClientRect().width, document.documentElement.clientWidth, label.height];'
                )[0];
                $this->assertLessThanOrEqual($lebarLayar, $lebarGrafik);
                $this->assertGreaterThanOrEqual(10, 12 * $lebarGrafik / 340, "Teks grafik terlalu kecil di 360px (lebar grafik {$lebarGrafik}px).");
                $this->assertGreaterThan(0, $hurufLabel);
            });
        });
    }

    public function test_menu_seluler_admin_memuat_tautan_harga_acuan_dan_kelola(): void
    {
        $this->harga(10, 3200);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin);

            $this->emulasi360($browser, function () use ($browser) {
                // Di Panel Admin (/admin), menu seluler menampilkan sidebar admin dan tombol beralih ke tampilan warga.
                $browser->visit('/admin')
                    ->assertVisible('@nav-toggle')
                    ->assertMissing('@nav-reference-prices')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-toggle', 'aria-expanded', 'true');

                $this->assertTautanMenuTerlihat($browser, 'switch-to-warga', route('dashboard'));
                $this->assertTautanMenuTerlihat($browser, 'nav-admin-reference-prices', route('admin.reference-prices.index'));
                $this->assertTautanMenuTerlihat($browser, 'nav-hubs', route('admin.hubs.index'));
                $this->assertTautanMenuTerlihat($browser, 'nav-users', route('admin.users.index'));
                $this->assertTanpaGeserHorizontal($browser, 'dashboard admin dengan menu seluler terbuka');

                $browser->click('@nav-admin-reference-prices')
                    ->waitForLocation('/admin/harga-acuan')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-admin-reference-prices', 'aria-current', 'page')
                    ->assertMissing('@nav-reference-prices')
                    ->click('@switch-to-warga')
                    ->waitForLocation('/dashboard')
                    ->click('@nav-toggle');

                $this->assertTautanMenuTerlihat($browser, 'nav-reference-prices', route('reference-prices.index'));
                $this->assertTautanMenuTerlihat($browser, 'switch-to-admin', route('admin.dashboard'));

                $browser->click('@nav-reference-prices')
                    ->waitForLocation('/harga-acuan')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-reference-prices', 'aria-current', 'page')
                    ->assertMissing('@nav-admin-reference-prices');

                $this->assertTanpaGeserHorizontal($browser, 'daftar harga acuan dengan menu seluler terbuka');
            });
        });
    }

    public function test_menu_seluler_tamu_memuat_tautan_harga_acuan(): void
    {
        $this->harga(10, 3200);

        $this->browse(function (Browser $browser) {
            $this->emulasi360($browser, function () use ($browser) {
                $browser->visit('/harga-acuan/plastik-pet/PA')
                    ->assertVisible('@nav-toggle')
                    ->click('@nav-toggle')
                    ->assertAttribute('@nav-toggle', 'aria-expanded', 'true')
                    ->assertMissing('@nav-admin-reference-prices')
                    ->assertAttribute('@nav-reference-prices', 'aria-current', 'page');

                $this->assertTautanMenuTerlihat($browser, 'nav-reference-prices', route('reference-prices.index'));
                $this->assertTanpaGeserHorizontal($browser, 'detail harga acuan dengan menu seluler terbuka');

                $browser->click('@nav-reference-prices')
                    ->waitForLocation('/harga-acuan')
                    ->assertVisible('@reference-price-table');
            });
        });
    }
}
