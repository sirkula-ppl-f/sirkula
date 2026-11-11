<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\ReferencePriceService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: harga untuk material aktif di provinsi baru, sudah berlaku (1–365 hari sebelum hari ini WIB),
 * diterbitkan admin baru.
 *
 * @extends Factory<ReferencePrice>
 */
class ReferencePriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            // Region tidak punya factory (milik modul wilayah), jadi provinsi dibuat langsung.
            'region_id' => fn () => Region::create([
                'name' => 'Provinsi Uji '.fake()->unique()->numerify('####'),
                'code' => 'UJI-'.fake()->unique()->numerify('######'),
            ])->id,
            'price_per_kg' => fake()->numberBetween(5, 200) * 100,
            'effective_date' => fn () => $this->today()->subDays(fake()->numberBetween(1, 365))->toDateString(),
            'source' => fake()->randomElement([
                'Survei harga pengepul',
                'Rata-rata harga bank sampah',
                'Laporan dinas lingkungan hidup provinsi',
            ]),
            'published_by' => User::factory()->admin(),
        ];
    }

    public function forMaterial(Material $material): static
    {
        return $this->state(['material_id' => $material->id]);
    }

    /**
     * Wilayah harga. Harus provinsi (parent_id null) agar sesuai aturan publikasi.
     */
    public function forRegion(Region $region): static
    {
        return $this->state(['region_id' => $region->id]);
    }

    public function effectiveOn(CarbonInterface|string $date): static
    {
        return $this->state([
            'effective_date' => $date instanceof CarbonInterface ? $date->format('Y-m-d') : $date,
        ]);
    }

    /**
     * Harga terjadwal: tanggal berlaku $days hari setelah hari ini (WIB).
     */
    public function scheduled(int $days = 7): static
    {
        return $this->state(fn () => [
            'effective_date' => $this->today()->addDays($days)->toDateString(),
        ]);
    }

    public function publishedBy(User $admin): static
    {
        return $this->state(['published_by' => $admin->id]);
    }

    private function today(): CarbonInterface
    {
        return app(ReferencePriceService::class)->today();
    }
}
