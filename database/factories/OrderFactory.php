<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Default: pesanan menunggu pembayaran, kedaluwarsa 24 jam lagi.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'SRK-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'total' => 112500,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'expires_at' => now()->addDay(),
            'paid_at' => null,
        ];
    }

    /**
     * Pesanan berstatus PAID (Lunas).
     */
    public function paid(?CarbonInterface $paidAt = null): static
    {
        return $this->state(fn () => [
            'status' => Order::STATUS_PAID,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Pesanan berstatus PAYMENT_REVIEW (Sedang Diperiksa).
     */
    public function paymentReview(): static
    {
        return $this->state([
            'status' => Order::STATUS_PAYMENT_REVIEW,
            'paid_at' => null,
        ]);
    }

    /**
     * Pesanan berstatus CANCELLED (Dibatalkan).
     */
    public function cancelled(): static
    {
        return $this->state([
            'status' => Order::STATUS_CANCELLED,
            'paid_at' => null,
        ]);
    }

    /**
     * Pesanan berstatus EXPIRED (Kedaluwarsa).
     */
    public function expired(): static
    {
        return $this->state([
            'status' => Order::STATUS_EXPIRED,
            'expires_at' => now()->subHour(),
            'paid_at' => null,
        ]);
    }

    public function lunas(): static
    {
        return $this->paid();
    }

    public function kedaluwarsa(): static
    {
        return $this->expired();
    }
}
