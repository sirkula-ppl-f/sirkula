<?php

namespace Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * Uji halaman beranda utama (Landing Page) Sirkula di desktop dan lebar 360px (SP-69 / NFR-002).
     */
    public function test_basic_example(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(1280, 800)
                ->visit('/')
                ->assertTitle(config('app.name'))
                ->assertSee('Ubah Barang Bekas & Material Daur Ulang Menjadi Nilai Baru')
                ->assertSee('Pratinjau Katalog Sirkula')
                ->assertVisible('@nav-brand')
                ->assertVisible('@hero-catalog-cta')
                ->assertVisible('@pillar-preloved')
                ->assertVisible('@pillar-recyclable')
                ->assertVisible('@pillar-upcycle')
                ->click('@showcase-filter-recyclable')
                ->assertSee('Botol PET Terpilah (lot 25 kg)')
                ->assertDontSee('Sepeda Lipat Bekas')
                ->click('@showcase-filter-all')
                ->assertSee('Sepeda Lipat Bekas');

            $devTools = new ChromeDevToolsDriver($browser->driver);
            $devTools->execute('Emulation.setDeviceMetricsOverride', [
                'width' => 360,
                'height' => 740,
                'deviceScaleFactor' => 1,
                'mobile' => true,
            ]);

            try {
                $browser->visit('/');

                [$scrollWidth, $innerWidth] = $browser->script(
                    'return [document.documentElement.scrollWidth, window.innerWidth];'
                )[0];

                $this->assertLessThanOrEqual($innerWidth, $scrollWidth);
            } finally {
                $devTools->execute('Emulation.clearDeviceMetricsOverride');
            }
        });
    }
}
