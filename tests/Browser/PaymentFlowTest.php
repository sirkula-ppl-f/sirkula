<?php

namespace Tests\Browser;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * PBI-13 / FR-016. Tombol bayar mengalihkan ke halaman Midtrans (jaringan luar), jadi tes ini
 * menguji halaman pembayaran dan halaman hasil. Verifikasi notifikasi diuji di tes Feature.
 */
class PaymentFlowTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function pesanan(User $pembeli, array $atribut = []): Order
    {
        return Order::factory()->create(array_merge(['buyer_id' => $pembeli->id, 'total' => 112500], $atribut));
    }

    public function test_halaman_pembayaran_menampilkan_ringkasan_dan_tombol_bayar(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.show', $order->id, false))
                ->assertSeeIn('@order-number', $order->order_number)
                ->assertSeeIn('@order-total', 'Rp112.500')
                ->assertSeeIn('@sandbox-notice', 'SANDBOX')
                ->assertPresent('@pay-button')
                ->assertSeeIn('@pay-button', 'Bayar via Midtrans (Sandbox)');
        });
    }

    public function test_pesanan_lunas_tidak_menampilkan_tombol_bayar(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli, ['status' => Order::STATUS_PAID, 'paid_at' => now()]);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.show', $order->id, false))
                ->assertMissing('@pay-button')
                ->assertSeeIn('@not-payable', 'tidak dapat dibayar');
        });
    }

    public function test_hasil_berhasil_berlabel_sandbox(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli, ['status' => Order::STATUS_PAID, 'paid_at' => now()]);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.result', $order->id, false))
                ->assertSeeIn('@payment-status', 'Pembayaran berhasil')
                ->assertSeeIn('@sandbox-label', 'SANDBOX')
                ->assertSeeIn('@payment-message', 'Serah-terima');
        });
    }

    public function test_hasil_sedang_diperiksa_bukan_gagal(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli, ['status' => Order::STATUS_PAYMENT_REVIEW]);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.result', $order->id, false))
                ->assertSeeIn('@payment-status', 'Pembayaran sedang diperiksa')
                ->assertDontSee('Pembayaran gagal');
        });
    }

    public function test_hasil_gagal(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli);
        Payment::create([
            'order_id' => $order->id,
            'provider_ref' => $order->order_number,
            'gross_amount' => $order->total,
            'status' => 'FAILED',
        ]);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.result', $order->id, false))
                ->assertSeeIn('@payment-status', 'Pembayaran gagal');
        });
    }

    public function test_menunggu_pembayaran_dan_link_kembali(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.result', $order->id, false))
                ->assertSeeIn('@payment-status', 'Menunggu pembayaran')
                ->click('@back-to-pay')
                ->assertPresent('@pay-button');
        });
    }

    public function test_parameter_redirect_tidak_membuat_pesanan_lunas(): void
    {
        $pembeli = User::factory()->create();
        $order = $this->pesanan($pembeli);

        $this->browse(function (Browser $browser) use ($pembeli, $order) {
            $browser->loginAs($pembeli)
                ->visit(route('payments.result', $order->id, false).'?transaction_status=settlement&status_code=200')
                ->assertSeeIn('@payment-status', 'Menunggu pembayaran');
        });

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_pesanan_orang_lain_tidak_bisa_dibuka(): void
    {
        $order = $this->pesanan(User::factory()->create());
        $lain = User::factory()->create();

        $this->browse(function (Browser $browser) use ($lain, $order) {
            $browser->loginAs($lain)
                ->visit(route('payments.show', $order->id, false))
                ->assertDontSee($order->order_number);
        });
    }
}
