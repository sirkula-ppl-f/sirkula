<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * STUB untuk PBI-11. Default: listing PRELOVED yang sudah disetujui, aktif, dan bersetok.
 *
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'listing_type' => ListingType::Preloved->value,
            'title' => fake()->words(3, true),
            'price' => fake()->numberBetween(10, 500) * 1000,
            'stock' => 5,
            'sale_unit' => 'ITEM',
            'review_status' => Listing::REVIEW_APPROVED,
            'publish_status' => Listing::PUBLISH_ACTIVE,
        ];
    }

    public function stok(int $stock): static
    {
        return $this->state(['stock' => $stock]);
    }

    public function belumDisetujui(): static
    {
        return $this->state(['review_status' => 'PENDING']);
    }
}
