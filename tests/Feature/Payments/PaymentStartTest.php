<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\MidtransGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\FakePaymentGateway;
use Tests\Support\InteractsWithMidtrans;
use Tests\TestCase;

class PaymentStartTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = $this->setUpMidtrans();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $order = Order::factory()->create();

        $this->get(route('payments.show', $order->id))->assertRedirect(route('login'));
        $this->post(route('payments.store', $order->id))->assertRedirect(route('login'));
        $this->get(route('payments.result', $order->id))->assertRedirect(route('login'));
    }

    public function test_pembeli_melihat_ringkasan_pembayaran(): void
    {
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id, 'total' => 112500]);

        $this->actingAs($pembeli)->get(route('payments.show', $order->id))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Rp112.500')
            ->assertSee('SANDBOX')
            ->assertSee('Bayar via Midtrans (Sandbox)');
    }

    public function test_pesanan_orang_lain_tidak_bisa_diakses(): void
    {
        $order = Order::factory()->create();
        $lain = User::factory()->create();

        $this->actingAs($lain)->get(route('payments.show', $order->id))->assertNotFound();
        $this->actingAs($lain)->post(route('payments.store', $order->id))->assertNotFound();
        $this->actingAs($lain)->get(route('payments.result', $order->id))->assertNotFound();
        $this->assertSame(0, Payment::count());
    }

    public function test_bayar_membuat_satu_payment_dengan_nominal_dari_server(): void
    {
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id, 'total' => 112500]);

        // Nominal dari input pengguna harus diabaikan.
        $this->actingAs($pembeli)
            ->post(route('payments.store', $order->id), ['gross_amount' => 1, 'total' => 1])
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v4/redirection/fake-'.$order->order_number);

        $payment = Payment::sole();
        $this->assertSame(112500, $payment->gross_amount);
        $this->assertSame('PENDING', $payment->status->value);
        $this->assertSame($order->order_number, $payment->provider_ref);
        $this->assertSame(112500, $this->gateway->calls[0]['amount']);
    }

    public function test_bayar_dua_kali_memakai_intent_yang_sama(): void
    {
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id]);

        $this->actingAs($pembeli)->post(route('payments.store', $order->id));
        $this->actingAs($pembeli)->post(route('payments.store', $order->id))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v4/redirection/fake-'.$order->order_number);

        $this->assertSame(1, Payment::count());
        $this->assertCount(1, $this->gateway->calls);
    }

    public function test_pesanan_lunas_atau_kedaluwarsa_tidak_bisa_dibayar(): void
    {
        $pembeli = User::factory()->create();

        foreach ([Order::factory()->lunas(), Order::factory()->kedaluwarsa()] as $factory) {
            $order = $factory->create(['buyer_id' => $pembeli->id]);

            $this->actingAs($pembeli)->post(route('payments.store', $order->id))
                ->assertSessionHasErrors('payment');
        }

        $this->assertSame(0, Payment::count());
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_pesanan_lewat_batas_waktu_tidak_bisa_dibayar_walau_status_masih_menunggu(): void
    {
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id, 'expires_at' => now()->subMinute()]);

        $this->actingAs($pembeli)->post(route('payments.store', $order->id))
            ->assertSessionHasErrors('payment');
    }

    public function test_gateway_gagal_menampilkan_pesan_dan_payment_tetap_unknown(): void
    {
        $this->gateway->shouldFail = true;
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id]);

        $this->actingAs($pembeli)->post(route('payments.store', $order->id))
            ->assertSessionHasErrors('payment');

        $payment = Payment::sole();
        $this->assertSame('UNKNOWN', $payment->status->value);
        $this->assertNull($payment->redirect_url);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_pembayaran_tidak_pernah_menetapkan_lunas_dari_halaman_atau_redirect(): void
    {
        $pembeli = User::factory()->create();
        $order = Order::factory()->create(['buyer_id' => $pembeli->id]);

        $this->actingAs($pembeli)->post(route('payments.store', $order->id));
        $this->actingAs($pembeli)->get(route('payments.result', $order->id).'?transaction_status=settlement&status_code=200');

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_mode_produksi_midtrans_ditolak(): void
    {
        config(['services.midtrans.is_production' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('produksi');

        (new MidtransGateway)->createSnapTransaction(Order::factory()->create(), 1000);
    }

    public function test_server_key_kosong_ditolak_sebelum_memanggil_jaringan(): void
    {
        config(['services.midtrans.server_key' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MIDTRANS_SERVER_KEY');

        (new MidtransGateway)->createSnapTransaction(Order::factory()->create(), 1000);
    }
}
