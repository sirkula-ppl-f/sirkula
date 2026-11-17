<?php

namespace Tests\Feature\CircularActivity;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CircularActivityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircularActivityPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('circular-activity.index'))->assertRedirect(route('login'));
    }

    public function test_warga_melihat_disclaimer_dan_ringkasan_miliknya(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'total' => 50_000,
        ]);
        OrderItem::factory()->recyclable(2.5)->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'subtotal' => 50_000,
        ]);

        $this->actingAs($warga)
            ->get(route('circular-activity.index'))
            ->assertOk()
            ->assertSee('Angka di halaman ini bersifat estimasi')
            ->assertSee('Rp 50.000')
            ->assertSee('5 kg')
            ->assertSee('Material Daur Ulang');
    }

    public function test_berat_tidak_diketahui_ditampilkan_belum_terdata_bukan_nol(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
        ]);
        OrderItem::factory()->preloved()->withoutWeight()->create(['order_id' => $order->id]);

        $response = $this->actingAs($warga)
            ->get(route('circular-activity.index'))
            ->assertOk()
            ->assertSee('Belum terdata')
            ->assertSee('1 item tanpa data berat');

        // Tidak ada nilai berat berupa angka (mis. "0 kg") di kartu mana pun.
        // Teks penjelasan "tidak dianggap 0 kg" tetap boleh muncul.
        $this->assertDoesNotMatchRegularExpression('/\d+(,\d+)? kg\s*<\/dd>/', $response->getContent());
    }

    public function test_tidak_menampilkan_transaksi_warga_lain(): void
    {
        $warga = User::factory()->create();
        $orangLain = User::factory()->create();
        $rekan = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $orangLain->id,
            'seller_id' => $rekan->id,
            'total' => 987_000,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'subtotal' => 987_000]);

        $this->actingAs($warga)
            ->get(route('circular-activity.index'))
            ->assertOk()
            ->assertDontSee('Rp 987.000');
    }

    public function test_filter_tidak_valid_termasuk_array_kembali_ke_default(): void
    {
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->get(route('circular-activity.index', ['periode' => 'ngawur', 'peran' => 'hacker']))
            ->assertOk()
            ->assertDontSee('Reset');

        $this->actingAs($warga)
            ->get(route('circular-activity.index', ['periode' => ['ngawur'], 'peran' => ['hacker']]))
            ->assertOk()
            ->assertDontSee('Reset');
    }

    public function test_grafik_bulanan_dan_tabel_riwayat_tampil_dengan_benar(): void
    {
        $nowWib = CarbonImmutable::create(2026, 11, 17, 14, 0, 0, CircularActivityService::TIMEZONE);
        $this->travelTo($nowWib);

        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $paidOrder = Order::factory()->paid($nowWib->utc())->create([
            'seller_id' => $warga->id,
            'buyer_id' => $rekan->id,
            'order_number' => 'ORD-TEST-2026-001',
            'total' => 60_000,
        ]);
        OrderItem::factory()->recyclable(3.0)->create([
            'order_id' => $paidOrder->id,
            'title_snapshot' => 'Kardus Bekas <script>alert(1)</script>',
            'quantity' => 2,
            'subtotal' => 60_000,
        ]);

        // Pesanan belum lunas tidak boleh muncul di tabel riwayat maupun grafik
        $unpaidOrder = Order::factory()->cancelled()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
            'order_number' => 'ORD-BATAL-999',
        ]);
        OrderItem::factory()->create([
            'order_id' => $unpaidOrder->id,
            'title_snapshot' => 'Barang Dibatalkan',
        ]);

        $response = $this->actingAs($warga)
            ->get(route('circular-activity.index'))
            ->assertOk()
            ->assertSee('Aktivitas Bulanan (6 Bulan Terakhir)')
            ->assertSee('Nov 2026')
            ->assertSee('17 Nov 2026, 14.00 WIB')
            ->assertSee('#ORD-TEST-2026-001')
            ->assertSee('Penjual')
            ->assertSee('6 kg')
            ->assertSee('Rp 60.000')
            ->assertDontSee('Barang Dibatalkan')
            ->assertDontSee('#ORD-BATAL-999');

        // Judul mengandung tag HTML harus di-escape (mencegah XSS, NFR-009).
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->getContent());
    }

    public function test_halaman_kosong_diarahkan_ke_halaman_terakhir_yang_berisi(): void
    {
        $warga = User::factory()->create();
        $rekan = User::factory()->create();

        $order = Order::factory()->paid()->create([
            'buyer_id' => $warga->id,
            'seller_id' => $rekan->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->actingAs($warga)
            ->get(route('circular-activity.index', ['page' => 99, 'peran' => 'buyer']))
            ->assertRedirect(route('circular-activity.index', ['peran' => 'buyer', 'page' => 1]));
    }

    public function test_akun_ditangguhkan_tidak_bisa_mengakses(): void
    {
        $warga = User::factory()->suspended()->create();

        $this->actingAs($warga)
            ->get(route('circular-activity.index'))
            ->assertRedirect(route('login'));
    }
}
