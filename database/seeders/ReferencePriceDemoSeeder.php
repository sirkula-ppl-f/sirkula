<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\ReferencePriceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Harga acuan contoh untuk demo di DB LOKAL. Tidak didaftarkan di DatabaseSeeder, karena harga
 * acuan sungguhan harus punya sumber nyata. Jalankan manual:
 *
 *     php artisan db:seed --class=ReferencePriceDemoSeeder
 *
 * - Ditolak bila DB aktif bukan DB lokal (DB online bersama tim tidak boleh diisi data demo).
 * - Entri harga acuan tidak bisa dihapus, jadi seeder dilewati bila data demo sudah ada.
 * - Butuh admin (AdminSeeder), material (MasterDataSeeder), dan provinsi (WilayahIndonesiaSeeder).
 */
class ReferencePriceDemoSeeder extends Seeder
{
    /**
     * Harga dasar contoh (Rp/kg) per slug material dari MasterDataSeeder.
     */
    private const BASE_PRICES = [
        'plastik-pet' => 4_000,
        'plastik-hdpe' => 3_500,
        'kertas' => 1_500,
        'kardus' => 2_000,
        'aluminium' => 12_000,
        'besi-dan-baja' => 4_500,
    ];

    /**
     * Kode provinsi contoh dari WilayahIndonesiaSeeder, tersebar di beberapa pulau.
     */
    private const PROVINCE_CODES = ['31', '32', '35', '51', '64', '73'];

    /**
     * Selisih harga per bulan (persen) supaya grafik tren tidak datar.
     */
    private const MONTHLY_CHANGES = [-6, -3, 0, 2, -1, 4];

    private const DEFAULT_BASE_PRICE = 2_500;

    public function run(ReferencePriceService $service): void
    {
        if (! $this->usesLocalDatabase()) {
            throw new RuntimeException(__('reference-prices.demo_seeder.not_local'));
        }

        $source = __('reference-prices.demo_seeder.source');

        if (ReferencePrice::query()->where('source', $source)->exists()) {
            $this->command?->warn(__('reference-prices.demo_seeder.already_seeded'));

            return;
        }

        $admin = User::query()->where('role', UserRole::Admin)->orderBy('id')->first();

        if ($admin === null) {
            $this->command?->warn(__('reference-prices.demo_seeder.missing_admin'));

            return;
        }

        $materials = $this->materials();
        $provinces = $this->provinces();

        if ($materials->isEmpty() || $provinces->isEmpty()) {
            $this->command?->warn(__('reference-prices.demo_seeder.missing_master_data'));

            return;
        }

        $count = DB::transaction(fn () => $this->publishAll($service, $admin, $materials, $provinces, $source));

        $this->command?->info(__('reference-prices.demo_seeder.done', ['count' => $count]));
    }

    /**
     * Enam bulan riwayat (tanggal 1 tiap bulan) per pasangan material + provinsi,
     * ditambah satu harga terjadwal agar label "Terjadwal" bisa dicoba.
     *
     * @param  Collection<int, Material>  $materials
     * @param  Collection<int, Region>  $provinces
     */
    private function publishAll(ReferencePriceService $service, User $admin, Collection $materials, Collection $provinces, string $source): int
    {
        $today = $service->today();
        $count = 0;

        foreach ($materials->values() as $m => $material) {
            $base = self::BASE_PRICES[$material->slug] ?? self::DEFAULT_BASE_PRICE;

            foreach ($provinces->values() as $p => $province) {
                $regional = $base + $p * 100;

                for ($monthsAgo = count(self::MONTHLY_CHANGES) - 1; $monthsAgo >= 0; $monthsAgo--) {
                    $change = self::MONTHLY_CHANGES[($monthsAgo + $m + $p) % count(self::MONTHLY_CHANGES)];

                    $service->publish([
                        'material_id' => $material->id,
                        'region_id' => $province->id,
                        'price_per_kg' => $this->roundTo50($regional * (100 + $change) / 100),
                        'effective_date' => $today->subMonthsNoOverflow($monthsAgo)->startOfMonth()->toDateString(),
                        'source' => $source,
                    ], $admin);
                    $count++;
                }
            }
        }

        $service->publish([
            'material_id' => $materials->first()->id,
            'region_id' => $provinces->first()->id,
            'price_per_kg' => $this->roundTo50((self::BASE_PRICES[$materials->first()->slug] ?? self::DEFAULT_BASE_PRICE) * 1.05),
            'effective_date' => $today->addDays(14)->toDateString(),
            'source' => $source,
        ], $admin);

        return $count + 1;
    }

    /**
     * Material aktif dengan harga dasar contoh; bila tidak ada, lima material aktif pertama.
     *
     * @return Collection<int, Material>
     */
    private function materials(): Collection
    {
        $materials = Material::active()->whereIn('slug', array_keys(self::BASE_PRICES))->orderBy('name')->get();

        return $materials->isNotEmpty() ? $materials : Material::active()->orderBy('name')->limit(5)->get();
    }

    /**
     * Provinsi contoh; bila kodenya tidak ada, lima provinsi pertama.
     *
     * @return Collection<int, Region>
     */
    private function provinces(): Collection
    {
        $provinces = Region::query()->whereNull('parent_id')->whereIn('code', self::PROVINCE_CODES)->orderBy('name')->get();

        return $provinces->isNotEmpty() ? $provinces : Region::query()->whereNull('parent_id')->orderBy('name')->limit(5)->get();
    }

    /**
     * SQLite, atau MySQL/MariaDB dengan host lokal.
     */
    private function usesLocalDatabase(): bool
    {
        $connection = config('database.connections.'.config('database.default'));

        if (($connection['driver'] ?? null) === 'sqlite') {
            return true;
        }

        return in_array((string) ($connection['host'] ?? ''), ['127.0.0.1', 'localhost', '::1'], true);
    }

    private function roundTo50(float $price): int
    {
        return (int) (round($price / 50) * 50);
    }
}
