<?php

namespace Tests\Browser;

use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthAccessibilityTest extends DuskTestCase
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

    private function assertFokusTerlihat(Browser $browser): void
    {
        [$focusVisible, $outlineStyle, $outlineWidth] = $browser->script(
            'const s = getComputedStyle(document.activeElement);'
            .'return [document.activeElement.matches(":focus-visible"), s.outlineStyle, s.outlineWidth];'
        )[0];

        $this->assertTrue($focusVisible, 'Elemen yang fokus harus cocok dengan :focus-visible.');
        $this->assertNotSame('none', $outlineStyle, 'Fokus harus punya outline yang terlihat.');
        $this->assertNotSame('0px', $outlineWidth, 'Outline fokus tidak boleh 0px.');
    }

    private function assertTanpaGeserHorizontal(Browser $browser, string $halaman): void
    {
        [$scrollWidth, $innerWidth] = $browser->script(
            'return [document.documentElement.scrollWidth, window.innerWidth];'
        )[0];

        $this->assertLessThanOrEqual(360, $innerWidth, 'Viewport tes harus selebar 360px.');
        $this->assertLessThanOrEqual(
            $innerWidth,
            $scrollWidth,
            "Halaman {$halaman} bergeser horizontal ({$scrollWidth}px > {$innerWidth}px)."
        );
    }

    public function test_halaman_auth_tidak_bergeser_horizontal_di_lebar_360px(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            // Jendela Chrome tidak bisa diperkecil di bawah ~500px, jadi lebar 360px
            // diemulasikan lewat DevTools seperti mode perangkat seluler.
            $devTools = new ChromeDevToolsDriver($browser->driver);
            $devTools->execute('Emulation.setDeviceMetricsOverride', [
                'width' => 360,
                'height' => 740,
                'deviceScaleFactor' => 1,
                'mobile' => true,
            ]);

            try {
                $halamanTamu = [
                    'login' => '/login',
                    'register' => '/register',
                    'forgot-password' => '/forgot-password',
                    'reset-password' => '/reset-password/token-contoh?email=contoh%40sirkula.test',
                ];

                foreach ($halamanTamu as $nama => $url) {
                    $browser->visit($url)->screenshot("360-{$nama}");
                    $this->assertTanpaGeserHorizontal($browser, $url);
                }

                $browser->loginAs($admin);

                foreach (['dashboard' => '/dashboard', 'admin' => '/admin'] as $nama => $url) {
                    $browser->visit($url)->screenshot("360-{$nama}");
                    $this->assertTanpaGeserHorizontal($browser, $url);
                }
            } finally {
                $devTools->execute('Emulation.clearDeviceMetricsOverride');
            }
        });
    }

    public function test_form_login_bisa_dioperasikan_dengan_keyboard_dan_fokus_terlihat(): void
    {
        User::factory()->create(['email' => 'warga@contoh.com', 'password' => 'rahasia123']);

        $this->browse(function (Browser $browser) {
            $browser->resize(1280, 800)->visit('/login');
            $this->assertSame('email', $this->duskAktif($browser), 'Fokus awal harus di kolom email.');

            $this->tekan($browser, 'warga@contoh.com', WebDriverKeys::TAB);
            $this->assertSame('password', $this->duskAktif($browser));
            $this->assertFokusTerlihat($browser);

            $this->tekan($browser, 'rahasia123', WebDriverKeys::TAB);
            $this->assertSame('password-toggle', $this->duskAktif($browser));
            $this->assertFokusTerlihat($browser);

            // Tombol tampil/sembunyi kata sandi bisa dipakai lewat Enter.
            $this->tekan($browser, WebDriverKeys::ENTER);
            $browser->assertAttribute('@password', 'type', 'text')
                ->assertSeeIn('@password-toggle', 'Sembunyikan');
            $this->tekan($browser, WebDriverKeys::ENTER);
            $browser->assertAttribute('@password', 'type', 'password');

            $this->tekan($browser, WebDriverKeys::TAB);
            $this->assertSame('forgot-password-link', $this->duskAktif($browser));
            $this->assertFokusTerlihat($browser);

            $this->tekan($browser, WebDriverKeys::TAB);
            $this->assertSame('login-button', $this->duskAktif($browser));
            $this->assertFokusTerlihat($browser);

            $this->tekan($browser, WebDriverKeys::ENTER);
            $browser->waitForLocation('/dashboard');
        });
    }

    public function test_tab_pertama_di_dashboard_memfokuskan_skip_link(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->resize(1280, 800)->loginAs($user)->visit('/dashboard');

            $this->tekan($browser, WebDriverKeys::TAB);

            [$teks, $lebar] = $browser->script(
                'return [document.activeElement.textContent.trim(), document.activeElement.getBoundingClientRect().width];'
            )[0];

            $this->assertSame('Lewati ke konten utama', $teks);
            $this->assertGreaterThan(1, $lebar, 'Skip-link harus terlihat saat difokus.');
            $this->assertFokusTerlihat($browser);
        });
    }
}
