<?php

namespace Tests\Feature\ReferencePrice;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\ReferencePriceService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\ReferencePriceDemoSeeder;
use Database\Seeders\WilayahIndonesiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class ReferencePriceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private const SUMBER_DEMO = 'Data contoh untuk demo';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));
    }

    private function siapkanDataMaster(): User
    {
        $this->seed([MasterDataSeeder::class, WilayahIndonesiaSeeder::class]);

        return User::factory()->admin()->create();
    }

    public function test_menerbitkan_riwayat_dan_harga_terjadwal_untuk_provinsi(): void
    {
        $admin = $this->siapkanDataMaster();

        $this->seed(ReferencePriceDemoSeeder::class);

        // 6 material × 6 provinsi × 6 bulan, ditambah 1 harga terjadwal.
        $this->assertDatabaseCount('reference_prices', 217);
        $this->assertSame(0, ReferencePrice::query()->where('source', '!=', self::SUMBER_DEMO)->count());
        $this->assertSame(0, ReferencePrice::query()->where('published_by', '!=', $admin->id)->count());
        $this->assertSame(0, ReferencePrice::query()->whereHas('region', fn ($q) => $q->whereNotNull('parent_id'))->count());
        $this->assertSame(1, ReferencePrice::query()->where('effective_date', '>', '2026-11-11')->count());
        $this->assertSame(0, ReferencePrice::query()->where('price_per_kg', '<', 1)->count());

        $terbaru = app(ReferencePriceService::class)->latestFor(
            Material::query()->where('slug', 'plastik-pet')->firstOrFail(),
            Region::query()->where('code', '31')->firstOrFail(),
        );
        $this->assertSame('2026-11-01', $terbaru->effective_date->toDateString());
    }

    public function test_dijalankan_ulang_tidak_menambah_data(): void
    {
        $this->siapkanDataMaster();

        $this->seed(ReferencePriceDemoSeeder::class);
        $jumlah = ReferencePrice::query()->count();
        $this->seed(ReferencePriceDemoSeeder::class);

        $this->assertDatabaseCount('reference_prices', $jumlah);
    }

    public function test_tidak_menerbitkan_apa_pun_bila_belum_ada_admin(): void
    {
        $this->seed([MasterDataSeeder::class, WilayahIndonesiaSeeder::class]);

        $this->seed(ReferencePriceDemoSeeder::class);

        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_ditolak_bila_db_aktif_bukan_db_lokal(): void
    {
        $this->siapkanDataMaster();
        $default = config('database.default');

        // Hanya config yang diubah; seeder menolak sebelum membuka koneksi ke host ini.
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'db.contoh.invalid',
        ]);

        try {
            (new ReferencePriceDemoSeeder)->run(app(ReferencePriceService::class));
            $this->fail('Seeder demo seharusnya ditolak di DB bukan lokal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('hanya untuk DB lokal', $e->getMessage());
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_tidak_didaftarkan_di_database_seeder(): void
    {
        $this->assertStringNotContainsString(
            'ReferencePriceDemoSeeder',
            (string) file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }
}
