<?php

namespace Tests\Browser;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Pengujian browser Laravel Dusk untuk Catatan Aktivitas Sirkular Pribadi (PBI-20 / FR-022).
 */
class CircularActivityBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_tamu_diarahkan_ke_login_saat_membuka_aktivitas_sirkular(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/aktivitas-sirkular')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }

    public function test_warga_melihat_ringkasan_grafik_dan_riwayat_tanpa_hitung_ganda(): void
    {
        $warga = User::factory()->create(['name' => 'Warga Sirkula']);
        $rekan = User::factory()->create();
        $orangLain = User::factory()->create();

        // Satu pesanan lunas berisi 2 item (1 dengan berat, 1 tanpa berat)
        $paidOrder = Order::factory()->paid(now()->subDay())->create([
            'seller_id' => $warga->id,
            'buyer_id' => $rekan->id,
            'order_number' => 'ORD-DUSK-001',
            'total' => 85_000,
        ]);

        OrderItem::factory()->recyclable(5.00)->create([
            'order_id' => $paidOrder->id,
            'title_snapshot' => 'Karung Botol PET Bersih',
            'quantity' => 2,
            'unit_price' => 25_000,
            'subtotal' => 50_000,
        ]);

        OrderItem::factory()->preloved()->withoutWeight()->create([
            'order_id' => $paidOrder->id,
            'title_snapshot' => 'Rak Buku Kayu Preloved',
            'quantity' => 1,
            'unit_price' => 35_000,
            'subtotal' => 35_000,
        ]);

        // Pesanan belum lunas milik warga (tidak boleh dihitung)
        $unpaidOrder = Order::factory()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'order_number' => 'ORD-DUSK-PENDING',
            'total' => 200_000,
        ]);
        OrderItem::factory()->recyclable(20.00)->create([
            'order_id' => $unpaidOrder->id,
            'title_snapshot' => 'Logam Belum Dibayar',
            'subtotal' => 200_000,
        ]);

        // Pesanan lunas milik warga lain (tidak boleh bocor)
        $otherOrder = Order::factory()->paid(now())->create([
            'buyer_id' => $orangLain->id,
            'seller_id' => $rekan->id,
            'order_number' => 'ORD-DUSK-OTHER',
            'total' => 500_000,
        ]);
        OrderItem::factory()->upcycle(2.00)->create([
            'order_id' => $otherOrder->id,
            'title_snapshot' => 'Tas Upcycle Warga Lain',
            'subtotal' => 500_000,
        ]);

        $this->browse(function (Browser $browser) use ($warga) {
            $browser->loginAs($warga)
                ->visit('/dashboard')
                ->waitFor('@nav-circular-activity')
                ->click('@nav-circular-activity')
                ->waitForLocation('/aktivitas-sirkular')
                ->waitFor('@circular-estimate-disclaimer')
                ->assertSeeIn('@circular-estimate-disclaimer', 'Angka di halaman ini bersifat estimasi')
                ->assertSeeIn('@circular-summary-orders', '1')
                ->assertSeeIn('@circular-summary-quantity', '3')
                ->assertSeeIn('@circular-summary-amount', 'Rp 85.000')
                ->assertSeeIn('@circular-summary-weight', '10 kg')
                ->assertSeeIn('@circular-summary-weight-missing', '1 item tanpa data berat')
                ->assertPresent('@circular-monthly-chart')
                ->assertSeeIn('@circular-history-table', 'Karung Botol PET Bersih')
                ->assertSeeIn('@circular-history-table', 'Rak Buku Kayu Preloved')
                ->assertSeeIn('@circular-history-table', 'Tidak diketahui')
                ->assertDontSee('Logam Belum Dibayar')
                ->assertDontSee('Tas Upcycle Warga Lain');
        });
    }

    public function test_warga_memfilter_peran_dan_mereset_filter(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $sellerOrder = Order::factory()->paid(now()->subHours(3))->create([
            'seller_id' => $warga->id,
            'buyer_id' => $rekan->id,
            'total' => 40_000,
        ]);
        OrderItem::factory()->recyclable(4.00)->create([
            'order_id' => $sellerOrder->id,
            'title_snapshot' => 'Kardus Lipat Penjual',
            'quantity' => 1,
            'subtotal' => 40_000,
        ]);

        $buyerOrder = Order::factory()->paid(now()->subHour())->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'total' => 75_000,
        ]);
        OrderItem::factory()->upcycle(1.50)->create([
            'order_id' => $buyerOrder->id,
            'title_snapshot' => 'Dompet Tenun Pembeli',
            'quantity' => 1,
            'subtotal' => 75_000,
        ]);

        $this->browse(function (Browser $browser) use ($warga) {
            $browser->loginAs($warga)
                ->visit('/aktivitas-sirkular')
                ->waitFor('@circular-summary-orders')
                ->assertSeeIn('@circular-summary-orders', '2')
                ->select('@circular-filter-role', 'seller')
                ->click('@circular-filter-apply')
                ->waitFor('@circular-filter-reset')
                ->assertSeeIn('@circular-summary-orders', '1')
                ->assertSeeIn('@circular-history-table', 'Kardus Lipat Penjual')
                ->assertDontSeeIn('@circular-history-table', 'Dompet Tenun Pembeli')
                ->click('@circular-filter-reset')
                ->waitForLocation('/aktivitas-sirkular')
                ->assertSeeIn('@circular-summary-orders', '2');
        });
    }

    public function test_tampilan_responsif_360px_tidak_melebar_horizontal(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $order = Order::factory()->paid(now())->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'total' => 30_000,
        ]);
        OrderItem::factory()->preloved(1.25)->create([
            'order_id' => $order->id,
            'title_snapshot' => 'Lampu Belajar Hemat Energi',
            'subtotal' => 30_000,
        ]);

        $this->browse(function (Browser $browser) use ($warga) {
            $devTools = new ChromeDevToolsDriver($browser->driver);
            $devTools->execute('Emulation.setDeviceMetricsOverride', [
                'width' => 360,
                'height' => 800,
                'deviceScaleFactor' => 1,
                'mobile' => true,
            ]);

            try {
                $browser->loginAs($warga)
                    ->visit('/aktivitas-sirkular')
                    ->waitFor('@circular-history-table')
                    ->assertVisible('@circular-estimate-disclaimer');

                [$scrollWidth, $innerWidth] = $browser->script(
                    'return [document.documentElement.scrollWidth, window.innerWidth];'
                )[0];

                $this->assertLessThanOrEqual(360, $innerWidth, 'Viewport tes harus selebar 360px.');
                $this->assertLessThanOrEqual(
                    $innerWidth,
                    $scrollWidth,
                    "Halaman mengalami horizontal overflow pada lebar layar 360px ({$scrollWidth}px > {$innerWidth}px)."
                );
            } finally {
                $devTools->execute('Emulation.clearDeviceMetricsOverride');
            }
        });
    }
}
