<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory pesanan untuk mendukung pengujian PBI-20 (catatan aktivitas sirkular).
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'checkout_key' => (string) Str::uuid(),
            'status' => OrderStatus::PendingPayment,
            'total' => 50_000,
            'expires_at' => now()->addHours(24),
            'paid_at' => null,
        ];
    }

    /**
     * Pesanan berstatus PAID (Lunas).
     */
    public function paid(?CarbonInterface $paidAt = null): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Pesanan berstatus PAYMENT_REVIEW (Sedang Diperiksa).
     */
    public function paymentReview(): static
    {
        return $this->state([
            'status' => OrderStatus::PaymentReview,
            'paid_at' => null,
        ]);
    }

    /**
     * Pesanan berstatus CANCELLED (Dibatalkan).
     */
    public function cancelled(): static
    {
        return $this->state([
            'status' => OrderStatus::Cancelled,
            'paid_at' => null,
        ]);
    }

    /**
     * Pesanan berstatus EXPIRED (Kedaluwarsa).
     */
    public function expired(): static
    {
        return $this->state([
            'status' => OrderStatus::Expired,
            'expires_at' => now()->subHour(),
            'paid_at' => null,
        ]);
    }
}
