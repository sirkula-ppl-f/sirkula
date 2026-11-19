<?php

namespace Tests\Feature\Payments;

use App\Jobs\ProcessPaymentEvent;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Services\Payments\PaymentStatusResolver;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\InteractsWithMidtrans;
use Tests\TestCase;

/**
 * PBI-13 / FR-016, NFR-003, NFR-006, NFR-011. Antrean memakai driver sync, jadi notifikasi
 * langsung diproses.
 */
class MidtransWebhookTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMidtrans();
    }

    public function test_settlement_menetapkan_pesanan_lunas(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order))
            ->assertOk()
            ->assertJson(['status' => 'accepted']);

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('PAID', $order->payment->status->value);
        $this->assertSame('bank_transfer', $order->payment->method);
        $this->assertSame(PaymentEvent::STATUS_PROCESSED, PaymentEvent::first()->process_status);
    }

    public function test_tanda_tangan_palsu_ditolak_tanpa_mengubah_data(): void
    {
        $order = $this->orderDenganPayment();
        $payload = $this->notification($order, 'settlement', ['signature_key' => str_repeat('a', 128)]);

        $this->postJson(route('payments.webhook'), $payload)->assertForbidden();

        $this->assertSame(0, PaymentEvent::count());
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
        $this->assertSame('PENDING', $order->fresh()->payment->status->value);
    }

    public function test_tanda_tangan_dari_nominal_yang_diubah_ditolak(): void
    {
        $order = $this->orderDenganPayment();
        $payload = $this->notification($order);
        $payload['gross_amount'] = '1.00'; // penyerang mengecilkan nominal, tanda tangan tetap yang lama

        $this->postJson(route('payments.webhook'), $payload)->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_payload_tidak_lengkap_ditolak(): void
    {
        $this->postJson(route('payments.webhook'), ['order_id' => 'SRK-1'])->assertStatus(400);

        $this->assertSame(0, PaymentEvent::count());
    }

    public function test_server_key_belum_diatur_menolak_semua_notifikasi(): void
    {
        $order = $this->orderDenganPayment();
        $payload = $this->notification($order);
        config(['services.midtrans.server_key' => '']);

        $this->postJson(route('payments.webhook'), $payload)->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_notifikasi_ganda_50_kali_hanya_tercatat_sekali(): void
    {
        $order = $this->orderDenganPayment();
        $payload = $this->notification($order);

        $pertama = $this->postJson(route('payments.webhook'), $payload);
        $pertama->assertJson(['status' => 'accepted']);
        $paidAt = $order->fresh()->paid_at;

        for ($i = 0; $i < 49; $i++) {
            $this->postJson(route('payments.webhook'), $payload)
                ->assertOk()
                ->assertJson(['status' => 'duplicate']);
        }

        $this->assertSame(1, PaymentEvent::count());
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertEquals($paidAt, $order->fresh()->paid_at);
    }

    public function test_notifikasi_tidak_berurutan_tidak_menurunkan_status_lunas(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'settlement', ['transaction_time' => '2026-11-15 10:05:00']));
        // Notifikasi "pending" yang lebih lama baru tiba setelah "settlement".
        $this->postJson(route('payments.webhook'), $this->notification($order, 'pending', ['status_code' => '201', 'transaction_time' => '2026-11-15 10:00:00']));

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame('PAID', $order->fresh()->payment->status->value);
        $this->assertSame(2, PaymentEvent::count());
    }

    public function test_pending_lalu_settlement_menjadi_lunas(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'pending', ['status_code' => '201']));
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);

        $this->postJson(route('payments.webhook'), $this->notification($order, 'settlement'));
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_nominal_tidak_cocok_tidak_menetapkan_lunas(): void
    {
        $order = $this->orderDenganPayment();
        // Tanda tangan valid untuk nominal 1.000, tetapi pesanan senilai 112.500.
        $payload = $this->notification($order, 'settlement', ['gross_amount' => '1000.00']);

        $this->postJson(route('payments.webhook'), $payload)->assertOk();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
        $this->assertSame(PaymentEvent::STATUS_REJECTED, PaymentEvent::first()->process_status);
    }

    public function test_pesanan_tidak_dikenal_diabaikan(): void
    {
        $order = Order::factory()->create(['order_number' => 'SRK-TANPA-PAYMENT']);

        $this->postJson(route('payments.webhook'), $this->notification($order))->assertOk();

        $this->assertSame(PaymentEvent::STATUS_IGNORED, PaymentEvent::first()->process_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_pembayaran_terlambat_setelah_kedaluwarsa_menjadi_payment_review(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'expire', ['status_code' => '407']));
        $this->assertSame('EXPIRED', $order->fresh()->payment->status->value);

        $this->postJson(route('payments.webhook'), $this->notification($order, 'settlement'));

        $order->refresh();
        $this->assertSame(Order::STATUS_PAYMENT_REVIEW, $order->status);
        $this->assertSame('EXPIRED', $order->payment->status->value);
        $this->assertNull($order->paid_at);
    }

    public function test_pembayaran_pada_pesanan_yang_sudah_dibatalkan_menjadi_payment_review(): void
    {
        $order = $this->orderDenganPayment(['status' => Order::STATUS_CANCELLED]);

        $this->postJson(route('payments.webhook'), $this->notification($order));

        $this->assertSame(Order::STATUS_PAYMENT_REVIEW, $order->fresh()->status);
    }

    public function test_deny_mencatat_pembayaran_gagal_tanpa_mengubah_pesanan(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'deny', ['status_code' => '202']));

        $this->assertSame('FAILED', $order->fresh()->payment->status->value);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status); // diurus PBI-15
    }

    public function test_capture_dengan_fraud_challenge_belum_lunas(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'capture', ['fraud_status' => 'challenge']));

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
        $this->assertSame('PENDING', $order->fresh()->payment->status->value);
    }

    public function test_notifikasi_disimpan_dulu_lalu_diproses_lewat_antrean(): void
    {
        Queue::fake();
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order))->assertOk();

        Queue::assertPushed(ProcessPaymentEvent::class);
        $event = PaymentEvent::first();
        $this->assertSame(PaymentEvent::STATUS_PENDING, $event->process_status);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);

        app(PaymentStatusResolver::class)->process($event);

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_memproses_event_yang_sama_dua_kali_aman(): void
    {
        Queue::fake();
        $order = $this->orderDenganPayment();
        $this->postJson(route('payments.webhook'), $this->notification($order));
        $event = PaymentEvent::first();

        app(PaymentStatusResolver::class)->process($event);
        $paidAt = $order->fresh()->paid_at;
        app(PaymentStatusResolver::class)->process($event);

        $this->assertEquals($paidAt, $order->fresh()->paid_at);
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
    }

    public function test_payload_yang_disimpan_tidak_memuat_tanda_tangan(): void
    {
        $order = $this->orderDenganPayment();

        $this->postJson(route('payments.webhook'), $this->notification($order));

        $this->assertArrayNotHasKey('signature_key', PaymentEvent::first()->payload);
    }

    public function test_route_webhook_bebas_csrf_dan_tanpa_login(): void
    {
        $route = app('router')->getRoutes()->getByName('payments.webhook');

        $this->assertContains(PreventRequestForgery::class, $route->excludedMiddleware());
        $this->assertNotContains('auth', $route->gatherMiddleware());
    }
}
