<?php

namespace Tests\Feature\ReferencePrice;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\ReferencePriceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReferencePriceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferencePriceService $service;

    private Material $material;

    private Region $provinsi;

    protected function setUp(): void
    {
        parent::setUp();

        // 12.00 WIB, 11 November 2026.
        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));

        $this->service = app(ReferencePriceService::class);
        $this->material = Material::factory()->create(['name' => 'Plastik PET']);
        $this->provinsi = Region::create(['name' => 'Provinsi Contoh', 'code' => 'P1']);
    }

    private function harga(string $tanggal, int $hargaPerKg = 3000, ?Material $material = null, ?Region $provinsi = null): ReferencePrice
    {
        return ReferencePrice::factory()
            ->forMaterial($material ?? $this->material)
            ->forRegion($provinsi ?? $this->provinsi)
            ->effectiveOn($tanggal)
            ->create(['price_per_kg' => $hargaPerKg]);
    }

    public function test_hari_ini_dihitung_dalam_wib(): void
    {
        $this->assertSame('2026-11-11', $this->service->today()->toDateString());
        $this->assertSame('Asia/Jakarta', $this->service->today()->getTimezone()->getName());

        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12.
        $this->travelTo(Carbon::parse('2026-11-11 23:30:00', 'UTC'));

        $this->assertSame('2026-11-12', $this->service->today()->toDateString());
        $this->assertSame('2027-02-10', $this->service->maxEffectiveDate()->toDateString());
    }

    public function test_terbitkan_menyimpan_data_dan_penerbit_dari_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $harga = $this->service->publish([
            'material_id' => (string) $this->material->id,
            'region_id' => (string) $this->provinsi->id,
            'price_per_kg' => '3500',
            'effective_date' => '2026-11-11',
            'source' => '  Survei harga pengepul  ',
        ], $admin);

        $this->assertDatabaseHas('reference_prices', [
            'id' => $harga->id,
            'material_id' => $this->material->id,
            'region_id' => $this->provinsi->id,
            'price_per_kg' => 3500,
            'effective_date' => '2026-11-11',
            'source' => 'Survei harga pengepul',
            'published_by' => $admin->id,
        ]);
        $this->assertTrue($harga->publisher->is($admin));
    }

    public function test_terbitkan_ditolak_bila_bukan_admin(): void
    {
        $this->expectException(AuthorizationException::class);

        try {
            $this->service->publish([
                'material_id' => $this->material->id,
                'region_id' => $this->provinsi->id,
                'price_per_kg' => 3500,
                'effective_date' => '2026-11-11',
                'source' => 'Survei',
            ], User::factory()->create());
        } finally {
            $this->assertDatabaseCount('reference_prices', 0);
        }
    }

    public function test_latest_for_mengambil_tanggal_berlaku_terbaru_yang_sudah_berlaku(): void
    {
        $this->harga('2026-08-01', 2500);
        $terbaru = $this->harga('2026-11-11', 3200);
        $this->harga('2026-10-01', 2800);

        $this->assertTrue($this->service->latestFor($this->material, $this->provinsi)->is($terbaru));
    }

    public function test_latest_for_mengabaikan_entri_terjadwal(): void
    {
        $berlaku = $this->harga('2026-10-01', 2800);
        $this->harga('2026-11-12', 3500);

        $this->assertTrue($this->service->latestFor($this->material, $this->provinsi)->is($berlaku));
    }

    public function test_latest_for_tanggal_sama_mengambil_id_terbesar(): void
    {
        $this->harga('2026-11-01', 3000);
        $koreksi = $this->harga('2026-11-01', 3100);

        $this->assertTrue($this->service->latestFor($this->material, $this->provinsi)->is($koreksi));
    }

    public function test_latest_for_null_bila_belum_ada_harga_yang_berlaku(): void
    {
        $this->assertNull($this->service->latestFor($this->material, $this->provinsi));

        // Harga material lain, provinsi lain, dan harga terjadwal tidak ikut terambil.
        $this->harga('2026-10-01', material: Material::factory()->create());
        $this->harga('2026-10-01', provinsi: Region::create(['name' => 'Provinsi Lain', 'code' => 'P2']));
        $this->harga('2026-12-01');

        $this->assertNull($this->service->latestFor($this->material, $this->provinsi));
    }

    public function test_latest_for_wilayah_kab_kota_naik_ke_provinsi_induk(): void
    {
        $kota = Region::create(['name' => 'Kota Contoh', 'code' => 'P1.01', 'parent_id' => $this->provinsi->id]);
        $harga = $this->harga('2026-10-01');

        $this->assertTrue($this->service->latestFor($this->material, $kota)->is($harga));
        $this->assertTrue($this->service->provinceOf($kota)->is($this->provinsi));
        $this->assertTrue($this->service->provinceOf($this->provinsi)->is($this->provinsi));
    }

    public function test_latest_for_null_bila_rantai_wilayah_induk_rusak(): void
    {
        $berputar = Region::create(['name' => 'Wilayah Rusak', 'code' => 'X1']);
        $berputar->forceFill(['parent_id' => $berputar->id])->save();

        $this->assertNull($this->service->provinceOf($berputar->fresh()));
        $this->assertNull($this->service->latestFor($this->material, $berputar->fresh()));
    }

    public function test_latest_for_dengan_waktu_on_memakai_tanggal_wib(): void
    {
        $juni = $this->harga('2026-06-01', 2000);
        $this->harga('2026-06-02', 2100);

        $this->assertTrue($this->service->latestFor($this->material, $this->provinsi, Carbon::parse('2026-06-01 16:59:00', 'UTC'))->is($juni));
        $this->assertSame(2100, $this->service->latestFor($this->material, $this->provinsi, Carbon::parse('2026-06-01 17:00:00', 'UTC'))->price_per_kg);
        $this->assertNull($this->service->latestFor($this->material, $this->provinsi, Carbon::parse('2026-05-31', 'Asia/Jakarta')));
    }

    public function test_batas_hari_mengikuti_wib_bukan_utc(): void
    {
        $kemarin = $this->harga('2026-11-11', 3000);
        $this->harga('2026-11-12', 3600);

        // 16.59 UTC tanggal 11 = 23.59 WIB tanggal 11: entri tanggal 12 belum berlaku.
        $this->travelTo(Carbon::parse('2026-11-11 16:59:00', 'UTC'));
        $this->assertTrue($this->service->latestFor($this->material, $this->provinsi)->is($kemarin));

        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12: entri tanggal 12 sudah berlaku walau UTC masih tanggal 11.
        $this->travelTo(Carbon::parse('2026-11-11 23:30:00', 'UTC'));
        $this->assertSame(3600, $this->service->latestFor($this->material, $this->provinsi)->price_per_kg);
    }
}
