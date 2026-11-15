<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * STUB untuk PBI-13. Default: pesanan menunggu pembayaran, kedaluwarsa 24 jam lagi.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'SRK-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'total' => 112500,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'expires_at' => now()->addDay(),
        ];
    }

    public function lunas(): static
    {
        return $this->state(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    }

    public function kedaluwarsa(): static
    {
        return $this->state(['status' => Order::STATUS_EXPIRED, 'expires_at' => now()->subHour()]);
    }
}
