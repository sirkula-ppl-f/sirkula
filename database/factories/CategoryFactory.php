<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Default: kategori aktif yang berlaku untuk SEMUA tipe listing (deterministik).
 * Pakai forTypes(...) bila test butuh tipe tertentu, misalnya
 * Category::factory()->forTypes(ListingType::Preloved)->create().
 *
 * Tipe listing dipasang setelah create(), jadi make() menghasilkan kategori tanpa tipe.
 *
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Category $category) {
            $category->categoryListingTypes()->createMany(
                array_map(fn (ListingType $type) => ['listing_type' => $type], ListingType::cases())
            );
        });
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Ganti tipe listing default (semua tipe) dengan tipe yang disebut saja.
     */
    public function forTypes(ListingType ...$types): static
    {
        return $this->afterCreating(function (Category $category) use ($types) {
            $category->categoryListingTypes()->delete();
            $category->categoryListingTypes()->createMany(
                array_map(fn (ListingType $type) => ['listing_type' => $type], $types)
            );
            $category->unsetRelation('categoryListingTypes');
        });
    }
}
