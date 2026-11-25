<?php

namespace Tests\Feature\ListingModeration;

use App\Enums\ListingModerationStatus;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\ListingModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithMidtrans;
use Tests\TestCase;

/**
 * PBI-06: notifikasi pembayaran yang sedang berjalan tetap diproses walaupun listing-nya sudah
 * dinonaktifkan admin. Pola sama dengan MidtransWebhookTest (antrean sync, tanpa jaringan).
 */
class ModeratedListingPaymentTest extends TestCase
{
    use InteractsWithMidtrans;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMidtrans();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Pesanan menunggu bayar (dengan payment intent) atas satu listing.
     *
     * @return array{0: Listing, 1: Order}
     */
    private function pesananBerjalan(): array
    {
        $listing = Listing::factory()->create(['price' => 112500]);
        $order = $this->orderDenganPayment(['seller_id' => $listing->user_id, 'total' => 112500]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'title_snapshot' => $listing->title,
            'unit_price' => 112500,
            'quantity' => 1,
            'subtotal' => 112500,
        ]);

        return [$listing, $order];
    }

    private function nonaktifkan(Listing $listing): void
    {
        app(ListingModerationService::class)->deactivate($listing, $this->admin, 'Listing dilaporkan tidak sesuai foto.', 'req-bayar-0001');

        $this->assertFalse($listing->fresh()->isPurchasable());
    }

    public function test_settlement_tetap_melunasi_pesanan_atas_listing_yang_sudah_dinonaktifkan(): void
    {
        [$listing, $order] = $this->pesananBerjalan();

        $this->nonaktifkan($listing);

        $this->postJson(route('payments.webhook'), $this->notification($order))
            ->assertOk()
            ->assertJson(['status' => 'accepted']);

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('PAID', $order->payment->status->value);
        $this->assertSame(PaymentEvent::STATUS_PROCESSED, PaymentEvent::sole()->process_status);

        // Listing tetap dinonaktifkan dan item pesanan tetap menunjuk listing yang sama.
        $this->assertSame(ListingModerationStatus::Hidden, $listing->fresh()->moderation_status);
        $this->assertSame($listing->id, OrderItem::sole()->listing_id);
    }

    public function test_pending_lalu_listing_dinonaktifkan_lalu_settlement_tetap_lunas(): void
    {
        [$listing, $order] = $this->pesananBerjalan();

        $this->postJson(route('payments.webhook'), $this->notification($order, 'pending', ['status_code' => '201']))->assertOk();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);

        $this->nonaktifkan($listing);

        $this->postJson(route('payments.webhook'), $this->notification($order, 'settlement', ['transaction_time' => '2026-11-15 10:05:00']))->assertOk();

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(2, PaymentEvent::query()->where('process_status', PaymentEvent::STATUS_PROCESSED)->count());
    }
}
