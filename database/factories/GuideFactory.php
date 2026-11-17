<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    protected $model = Guide::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'body' => fake()->sentence(12),
            'status' => 'published',
            'views_count' => 0,
            'interactions_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    // Guide::factory()->forPlacements('listing_form', 'dashboard')->create()
    // Tanpa ini, panduan dibuat tanpa lokasi tampil.
    public function forPlacements(string ...$placements): static
    {
        return $this->afterCreating(fn (Guide $guide) => $guide->syncPlacements($placements));
    }
}
