<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory item pesanan (snapshot) untuk mendukung pengujian PBI-20.
 *
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(10, 200) * 1000;
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'listing_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'type_snapshot' => ListingType::Preloved,
            'unit_price' => $unitPrice,
            'weight_per_unit_snapshot' => 1.50,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }

    /**
     * Item bertipe Barang Bekas (PRELOVED). Berat opsional (bisa null).
     */
    public function preloved(?float $weightKg = 1.20): static
    {
        return $this->state([
            'type_snapshot' => ListingType::Preloved,
            'weight_per_unit_snapshot' => $weightKg,
        ]);
    }

    /**
     * Item bertipe Material Daur Ulang per lot (RECYCLABLE). Berat per lot wajib terisi.
     */
    public function recyclable(float $weightPerLotKg = 5.00): static
    {
        return $this->state([
            'type_snapshot' => ListingType::Recyclable,
            'weight_per_unit_snapshot' => $weightPerLotKg,
        ]);
    }

    /**
     * Item bertipe Produk Upcycle (UPCYCLE). Berat opsional (bisa null).
     */
    public function upcycle(?float $weightKg = 0.80): static
    {
        return $this->state([
            'type_snapshot' => ListingType::Upcycle,
            'weight_per_unit_snapshot' => $weightKg,
        ]);
    }

    /**
     * Item dengan berat yang tidak diketahui (null) untuk menguji aturan FR-022.
     */
    public function withoutWeight(): static
    {
        return $this->state([
            'weight_per_unit_snapshot' => null,
        ]);
    }
}
