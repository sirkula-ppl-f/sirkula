<?php

namespace Tests\Browser;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Material;
use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\JsonWireCompat;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * PBI-04 / FR-007. Alur admin kategori dan material, dialog konfirmasi
 * (resources/js/confirm-dialog.js), dan tampilan di lebar 360px.
 */
class MasterDataTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Kirim tombol keyboard ke elemen yang sedang fokus.
     */
    private function tekan(Browser $browser, string ...$tombol): void
    {
        $browser->driver->getKeyboard()->sendKeys($tombol);
    }

    private function duskAktif(Browser $browser): ?string
    {
        return $browser->script('return document.activeElement.getAttribute("dusk");')[0];
    }

    /**
     * Urutan aksi mouse sungguhan (W3C Actions), jadi pointerdown, pointerup, dan click ikut terpicu.
     * clickAtPoint() Dusk hanya memanggil element.click() lewat JS tanpa pointerdown, sehingga
     * tidak bisa dipakai untuk menguji tutup lewat backdrop. Setiap urutan diakhiri pointerUp.
     *
     * @param  list<array<string, mixed>>  $aksi
     */
    private function aksiPointer(Browser $browser, array $aksi): void
    {
        $browser->driver->execute(DriverCommand::ACTIONS, [
            'actions' => [[
                'type' => 'pointer',
                'id' => 'mouse',
                'parameters' => ['pointerType' => 'mouse'],
                'actions' => $aksi,
            ]],
        ]);
    }

    private function klikDiTitik(Browser $browser, int $x, int $y): void
    {
        $this->aksiPointer($browser, [
            ['type' => 'pointerMove', 'duration' => 0, 'origin' => 'viewport', 'x' => $x, 'y' => $y],
            ['type' => 'pointerDown', 'button' => 0],
            ['type' => 'pointerUp', 'button' => 0],
        ]);
    }

    private function bukaDaftar(Browser $browser, User $admin, string $nav, string $path): void
    {
        $browser->loginAs($admin)
            ->visit('/admin')
            ->click('@'.$nav)
            ->waitForLocation($path);
    }

    private function dialogTerbuka(Browser $browser): bool
    {
        return $browser->script('return document.querySelector("[data-confirm-dialog]").open;')[0];
    }

    /**
     * Tanda di halaman saat ini. Hilang bila form ternyata terkirim dan halaman berpindah.
     */
    private function tandaiHalaman(Browser $browser): void
    {
        $browser->script('window.halamanAwal = true;');
    }

    private function assertMasihDiHalamanYangSama(Browser $browser): void
    {
        $this->assertTrue(
            $browser->script('return window.halamanAwal === true;')[0],
            'Form tidak boleh terkirim sebelum dikonfirmasi.'
        );
    }

    /**
     * Hasil Batal, Esc, dan klik backdrop: dialog tertutup, fokus kembali ke tombol pemicu,
     * dan tombol tidak dinonaktifkan oleh data-submit-once.
     */
    private function assertDialogTertutupDanFokusKembali(Browser $browser, string $tombol): void
    {
        $browser->waitUntilMissing('@confirm-dialog')
            ->assertAttributeMissing('@'.$tombol, 'disabled');

        $this->assertFalse($this->dialogTerbuka($browser));
        $this->assertSame($tombol, $this->duskAktif($browser), 'Fokus harus kembali ke tombol yang membuka dialog.');
        $this->assertFalse(
            $browser->script('return document.querySelector(\'[dusk="'.$tombol.'"]\').form.hasAttribute("aria-busy");')[0],
            'Form tidak boleh ditandai sedang dikirim setelah dialog ditutup tanpa konfirmasi.'
        );
        $this->assertMasihDiHalamanYangSama($browser);
    }

    /**
     * Jendela Chrome tidak bisa diperkecil di bawah ~500px, jadi lebar 360px diemulasikan lewat
     * DevTools (pola yang sama dengan AuthAccessibilityTest).
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

    private function assertDialogDiDalamViewport(Browser $browser): void
    {
        [$kiri, $atas, $kanan, $bawah, $lebar, $tinggi] = $browser->script(
            'const r = document.querySelector("[data-confirm-dialog]").getBoundingClientRect();'
            .'return [r.left, r.top, r.right, r.bottom, document.documentElement.clientWidth, window.innerHeight];'
        )[0];

        $this->assertGreaterThanOrEqual(0, $kiri, 'Dialog terpotong di kiri.');
        $this->assertGreaterThanOrEqual(0, $atas, 'Dialog terpotong di atas.');
        $this->assertLessThanOrEqual($lebar, $kanan, 'Dialog terpotong di kanan.');
        $this->assertLessThanOrEqual($tinggi, $bawah, 'Dialog terpotong di bawah.');
    }

    /**
     * Nama 100 huruf tanpa spasi (batas validasi), untuk menguji teks yang tidak bisa dipatahkan di spasi.
     */
    private function namaPanjang(): string
    {
        return str_repeat('Daurulang', 11).'X';
    }

    public function test_alur_kategori_tambah_ubah_nonaktifkan_aktifkan_dan_hapus(): void
    {
        $admin = User::factory()->admin()->create();
        $dipakai = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
        Listing::factory()->create(['category_id' => $dipakai->id]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');

            // Kategori yang dipakai listing hanya bisa dinonaktifkan.
            $browser->assertSeeIn('@category-row-elektronik', 'Ya')
                ->assertPresent('@deactivate-elektronik')
                ->assertMissing('@delete-elektronik');

            $browser->click('@category-create-link')
                ->waitForLocation('/admin/kategori/tambah')
                ->type('@name', 'Perabot Rumah')
                ->type('@description', 'Meja, kursi, dan lemari bekas.')
                ->check('@listing-type-preloved')
                ->click('@save-button')
                ->waitForLocation('/admin/kategori')
                ->waitForTextIn('@alert', 'Kategori "Perabot Rumah" berhasil ditambahkan.')
                ->assertSeeIn('@category-row-perabot-rumah', 'Barang Bekas')
                ->assertSeeIn('@category-row-perabot-rumah', 'Tidak')
                ->assertPresent('@delete-perabot-rumah');

            // Slug tidak berubah walau nama diubah.
            $browser->click('@edit-perabot-rumah')
                ->waitFor('@category-slug')
                ->assertSeeIn('@category-slug', 'perabot-rumah')
                ->assertInputValue('@name', 'Perabot Rumah')
                ->type('@name', 'Perabot dan Dekorasi Rumah')
                ->check('@listing-type-upcycle')
                ->click('@save-button')
                ->waitForLocation('/admin/kategori')
                ->waitForTextIn('@alert', 'Kategori "Perabot dan Dekorasi Rumah" berhasil diperbarui.')
                ->assertSeeIn('@category-row-perabot-rumah', 'Perabot dan Dekorasi Rumah')
                ->assertSeeIn('@category-row-perabot-rumah', 'Produk Upcycle');

            $browser->click('@deactivate-perabot-rumah')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan kategori?')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan kategori “Perabot dan Dekorasi Rumah”?')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Kategori "Perabot dan Dekorasi Rumah" dinonaktifkan.')
                ->assertSeeIn('@category-row-perabot-rumah', 'Nonaktif')
                ->assertPresent('@activate-perabot-rumah')
                ->assertMissing('@deactivate-perabot-rumah');

            $this->assertFalse(Category::where('slug', 'perabot-rumah')->sole()->is_active);

            // Aktifkan tanpa dialog: bila dialog muncul, form tidak terkirim dan flash tidak pernah tampil.
            $browser->click('@activate-perabot-rumah')
                ->waitForTextIn('@alert', 'Kategori "Perabot dan Dekorasi Rumah" diaktifkan kembali.')
                ->assertPresent('@deactivate-perabot-rumah');

            $this->assertTrue(Category::where('slug', 'perabot-rumah')->sole()->is_active);

            $browser->click('@delete-perabot-rumah')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Hapus kategori?')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Kategori "Perabot dan Dekorasi Rumah" berhasil dihapus.')
                ->assertMissing('@category-row-perabot-rumah')
                ->assertPresent('@category-row-elektronik');
        });

        $this->assertDatabaseMissing('categories', ['slug' => 'perabot-rumah']);
        $this->assertTrue($dipakai->fresh()->is_active);
    }

    public function test_alur_material_tambah_ubah_nonaktifkan_aktifkan_dan_hapus(): void
    {
        $admin = User::factory()->admin()->create();
        $dipakai = Material::factory()->create(['name' => 'Logam', 'slug' => 'logam']);
        Listing::factory()->create(['material_id' => $dipakai->id]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-materials', '/admin/material');

            // Material yang dipakai listing hanya bisa dinonaktifkan.
            $browser->assertSeeIn('@material-row-logam', 'Ya')
                ->assertPresent('@deactivate-logam')
                ->assertMissing('@delete-logam');

            $browser->click('@material-create-link')
                ->waitForLocation('/admin/material/tambah')
                ->type('@name', 'Kaca Bening')
                ->type('@description', 'Botol dan toples kaca bening.')
                ->click('@save-button')
                ->waitForLocation('/admin/material')
                ->waitForTextIn('@alert', 'Material "Kaca Bening" berhasil ditambahkan.')
                ->assertSeeIn('@material-row-kaca-bening', 'Tidak')
                ->assertPresent('@delete-kaca-bening');

            // Slug tidak berubah walau nama diubah.
            $browser->click('@edit-kaca-bening')
                ->waitFor('@material-slug')
                ->assertSeeIn('@material-slug', 'kaca-bening')
                ->assertInputValue('@name', 'Kaca Bening')
                ->type('@name', 'Kaca Bening Bekas')
                ->click('@save-button')
                ->waitForLocation('/admin/material')
                ->waitForTextIn('@alert', 'Material "Kaca Bening Bekas" berhasil diperbarui.')
                ->assertSeeIn('@material-row-kaca-bening', 'Kaca Bening Bekas');

            $browser->click('@deactivate-kaca-bening')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan material?')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan material “Kaca Bening Bekas”?')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Material "Kaca Bening Bekas" dinonaktifkan.')
                ->assertSeeIn('@material-row-kaca-bening', 'Nonaktif')
                ->assertPresent('@activate-kaca-bening')
                ->assertMissing('@deactivate-kaca-bening');

            $this->assertFalse(Material::where('slug', 'kaca-bening')->sole()->is_active);

            // Aktifkan tanpa dialog: bila dialog muncul, form tidak terkirim dan flash tidak pernah tampil.
            $browser->click('@activate-kaca-bening')
                ->waitForTextIn('@alert', 'Material "Kaca Bening Bekas" diaktifkan kembali.')
                ->assertPresent('@deactivate-kaca-bening');

            $this->assertTrue(Material::where('slug', 'kaca-bening')->sole()->is_active);

            $browser->click('@delete-kaca-bening')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Hapus material?')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Material "Kaca Bening Bekas" berhasil dihapus.')
                ->assertMissing('@material-row-kaca-bening')
                ->assertPresent('@material-row-logam');
        });

        $this->assertDatabaseMissing('materials', ['slug' => 'kaca-bening']);
        $this->assertTrue($dipakai->fresh()->is_active);
    }

    public function test_dialog_terbuka_dengan_fokus_di_batal_dan_batal_tidak_mengirim_form(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');
            $this->tandaiHalaman($browser);

            $browser->click('@deactivate-elektronik')->waitFor('@confirm-dialog');

            $this->assertTrue($this->dialogTerbuka($browser));
            $this->assertSame('confirm-dialog-cancel', $this->duskAktif($browser), 'Fokus awal dialog harus di tombol Batal.');

            $browser->click('@confirm-dialog-cancel');
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');

            $browser->assertPathIs('/admin/kategori')->assertMissing('@alert');
        });

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_esc_dan_klik_backdrop_menutup_dialog_seperti_batal(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');
            $this->tandaiHalaman($browser);

            $browser->click('@deactivate-elektronik')->waitFor('@confirm-dialog');
            $this->tekan($browser, WebDriverKeys::ESCAPE);
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');

            // Pojok kiri atas berada di luar kotak dialog, jadi klik mengenai backdrop.
            $browser->click('@deactivate-elektronik')->waitFor('@confirm-dialog');
            $this->klikDiTitik($browser, 5, 5);
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');

            $browser->assertPathIs('/admin/kategori')->assertMissing('@alert');
        });

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_seret_seleksi_teks_keluar_dialog_tidak_menutupnya(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');
            $this->tandaiHalaman($browser);

            $browser->click('@deactivate-elektronik')->waitFor('@confirm-dialog');

            // Tekan di teks pesan, seret ke backdrop, lalu lepas di sana.
            $pesan = $browser->driver->findElement(WebDriverBy::id('confirm-dialog-message'));
            $this->aksiPointer($browser, [
                ['type' => 'pointerMove', 'duration' => 0, 'origin' => [JsonWireCompat::WEB_DRIVER_ELEMENT_IDENTIFIER => $pesan->getID()], 'x' => 0, 'y' => 0],
                ['type' => 'pointerDown', 'button' => 0],
                ['type' => 'pointerMove', 'duration' => 100, 'origin' => 'viewport', 'x' => 5, 'y' => 5],
                ['type' => 'pointerUp', 'button' => 0],
            ]);

            $this->assertNotSame('', $browser->script('return window.getSelection().toString();')[0], 'Seret harus menyeleksi teks pesan.');
            $this->assertTrue($this->dialogTerbuka($browser), 'Dialog tidak boleh tertutup oleh seleksi teks yang diseret keluar.');

            $browser->click('@confirm-dialog-cancel');
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');
        });

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_varian_tombol_konfirmasi_mengikuti_aksi_terakhir(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');
            $this->tandaiHalaman($browser);

            $browser->click('@delete-elektronik')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Hapus kategori?')
                ->assertAttribute('@confirm-dialog-confirm', 'data-variant', 'danger');
            $this->assertSame('Hapus', $browser->text('@confirm-dialog-confirm'));

            $browser->click('@confirm-dialog-cancel');
            $this->assertDialogTertutupDanFokusKembali($browser, 'delete-elektronik');

            $browser->click('@deactivate-elektronik')
                ->waitFor('@confirm-dialog')
                ->assertSeeIn('@confirm-dialog', 'Nonaktifkan kategori?')
                ->assertAttributeMissing('@confirm-dialog-confirm', 'data-variant');
            $this->assertSame('Nonaktifkan', $browser->text('@confirm-dialog-confirm'));

            $browser->click('@confirm-dialog-cancel');
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');
        });

        $category->refresh();
        $this->assertTrue($category->is_active);
    }

    public function test_setelah_batal_konfirmasi_mengirim_form_sekali(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->bukaDaftar($browser, $admin, 'nav-categories', '/admin/kategori');

            // Hitung kiriman form aksi yang benar-benar diteruskan browser. Form method="dialog" di dalam
            // dialog (Batal/konfirmasi) tidak dihitung. sessionStorage bertahan setelah halaman berpindah.
            $browser->script(<<<'JS'
                sessionStorage.setItem('jumlahKirim', '0');
                document.addEventListener('submit', (event) => {
                    if (event.target.matches('form[data-confirm]') && !event.defaultPrevented) {
                        sessionStorage.setItem('jumlahKirim', String(Number(sessionStorage.getItem('jumlahKirim')) + 1));
                    }
                });
                JS);
            $this->tandaiHalaman($browser);

            $browser->click('@deactivate-elektronik')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-cancel');
            $this->assertDialogTertutupDanFokusKembali($browser, 'deactivate-elektronik');
            $this->assertSame('0', $browser->script('return sessionStorage.getItem("jumlahKirim");')[0]);

            $browser->click('@deactivate-elektronik')
                ->waitFor('@confirm-dialog')
                ->click('@confirm-dialog-confirm')
                ->waitForTextIn('@alert', 'Kategori "Elektronik" dinonaktifkan.')
                ->assertPresent('@activate-elektronik');

            $this->assertSame('1', $browser->script('return sessionStorage.getItem("jumlahKirim");')[0], 'Form harus terkirim tepat satu kali.');
        });

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_halaman_kategori_tanpa_geser_horizontal_di_360px(): void
    {
        $admin = User::factory()->admin()->create();
        $nama = $this->namaPanjang();
        $category = Category::factory()->create(['name' => $nama, 'slug' => Str::slug($nama)]);

        $this->assertSame(100, mb_strlen($nama));

        $this->browse(function (Browser $browser) use ($admin, $category) {
            $browser->loginAs($admin);

            $this->emulasi360($browser, function () use ($browser, $category) {
                $browser->visit('/admin/kategori')->screenshot('360-kategori-daftar');
                $this->assertTanpaGeserHorizontal($browser, 'daftar kategori');

                $browser->click('@deactivate-'.$category->slug)
                    ->waitFor('@confirm-dialog')
                    ->screenshot('360-kategori-dialog');
                $this->assertDialogDiDalamViewport($browser);
                $this->assertTanpaGeserHorizontal($browser, 'daftar kategori dengan dialog terbuka');

                $browser->click('@confirm-dialog-confirm')
                    ->waitForTextIn('@alert', $category->name)
                    ->screenshot('360-kategori-flash');
                $this->assertTanpaGeserHorizontal($browser, 'daftar kategori dengan flash nama panjang');

                $browser->visit('/admin/kategori/tambah')->screenshot('360-kategori-tambah');
                $this->assertTanpaGeserHorizontal($browser, 'tambah kategori');

                $browser->visit(route('admin.categories.edit', $category, false))
                    ->assertSeeIn('@category-slug', $category->slug)
                    ->assertInputValue('@name', $category->name)
                    ->screenshot('360-kategori-ubah');
                $this->assertTanpaGeserHorizontal($browser, 'ubah kategori');
            });
        });
    }

    public function test_halaman_material_tanpa_geser_horizontal_di_360px(): void
    {
        $admin = User::factory()->admin()->create();
        $nama = $this->namaPanjang();
        $material = Material::factory()->create(['name' => $nama, 'slug' => Str::slug($nama)]);

        $this->browse(function (Browser $browser) use ($admin, $material) {
            $browser->loginAs($admin);

            $this->emulasi360($browser, function () use ($browser, $material) {
                $browser->visit('/admin/material')->screenshot('360-material-daftar');
                $this->assertTanpaGeserHorizontal($browser, 'daftar material');

                $browser->click('@deactivate-'.$material->slug)
                    ->waitFor('@confirm-dialog')
                    ->screenshot('360-material-dialog');
                $this->assertDialogDiDalamViewport($browser);
                $this->assertTanpaGeserHorizontal($browser, 'daftar material dengan dialog terbuka');

                $browser->click('@confirm-dialog-confirm')
                    ->waitForTextIn('@alert', $material->name)
                    ->screenshot('360-material-flash');
                $this->assertTanpaGeserHorizontal($browser, 'daftar material dengan flash nama panjang');

                $browser->visit('/admin/material/tambah')->screenshot('360-material-tambah');
                $this->assertTanpaGeserHorizontal($browser, 'tambah material');

                $browser->visit(route('admin.materials.edit', $material, false))
                    ->assertSeeIn('@material-slug', $material->slug)
                    ->assertInputValue('@name', $material->name)
                    ->screenshot('360-material-ubah');
                $this->assertTanpaGeserHorizontal($browser, 'ubah material');
            });
        });
    }
}
