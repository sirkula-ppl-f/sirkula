<?php

namespace Tests\Feature\ReferencePrice;

use App\Exceptions\MasterDataInUseException;
use App\Models\Listing;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Services\MaterialService;
use App\Services\ReferencePriceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Harga acuan terhubung ke data lain lewat FK restrict dan tidak pernah mengubah harga penjual.
 */
class ReferencePriceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jalankan $hapus dan pastikan database menolaknya karena foreign key.
     */
    private function assertDitolakForeignKey(callable $hapus): void
    {
        try {
            $hapus();
            $this->fail('Penghapusan seharusnya ditolak foreign key restrict.');
        } catch (QueryException $e) {
            $this->assertSame('23000', $e->getCode());
        }
    }

    public function test_material_yang_punya_harga_acuan_tidak_bisa_dihapus_di_database(): void
    {
        $harga = ReferencePrice::factory()->create();

        $this->assertDitolakForeignKey(fn () => DB::table('materials')->where('id', $harga->material_id)->delete());

        $this->assertDatabaseHas('materials', ['id' => $harga->material_id]);
    }

    public function test_provinsi_yang_punya_harga_acuan_tidak_bisa_dihapus(): void
    {
        $harga = ReferencePrice::factory()->create();

        $this->assertDitolakForeignKey(fn () => Region::query()->whereKey($harga->region_id)->delete());

        $this->assertDatabaseHas('regions', ['id' => $harga->region_id]);
    }

    public function test_admin_penerbit_tidak_bisa_dihapus(): void
    {
        $harga = ReferencePrice::factory()->create();

        $this->assertDitolakForeignKey(fn () => User::query()->whereKey($harga->published_by)->delete());

        $this->assertDatabaseHas('users', ['id' => $harga->published_by]);
    }

    public function test_material_yang_punya_harga_acuan_dipakai_dan_hanya_bisa_dinonaktifkan(): void
    {
        $material = Material::factory()->create();
        ReferencePrice::factory()->forMaterial($material)->create();
        $hargaSebelum = DB::table('reference_prices')->get();
        $service = app(MaterialService::class);

        $this->assertTrue($material->isInUse());
        $this->assertTrue((bool) Material::withInUse()->find($material->id)->in_use);

        try {
            $service->delete($material);
            $this->fail('Material yang punya harga acuan seharusnya tidak bisa dihapus.');
        } catch (MasterDataInUseException $e) {
            $this->assertSame('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.', $e->getMessage());
        }

        $service->deactivate($material);

        $this->assertFalse($material->fresh()->is_active);
        $this->assertEquals($hargaSebelum, DB::table('reference_prices')->get());
    }

    public function test_harga_penjual_tidak_berubah_saat_harga_acuan_diterbitkan(): void
    {
        $material = Material::factory()->create();
        $provinsi = Region::create(['name' => 'Provinsi Contoh', 'code' => 'P1']);
        $listing = Listing::factory()->create(['material_id' => $material->id, 'price' => 12_000]);
        $listingLain = Listing::factory()->create(['price' => 50_000]);
        $listingSebelum = DB::table('listings')->orderBy('id')->get();
        $service = app(ReferencePriceService::class);
        $admin = User::factory()->admin()->create();

        foreach ([3_500, 4_200] as $hargaPerKg) {
            $service->publish([
                'material_id' => $material->id,
                'region_id' => $provinsi->id,
                'price_per_kg' => $hargaPerKg,
                'effective_date' => $service->today()->toDateString(),
                'source' => 'Survei harga pengepul',
            ], $admin);
        }

        $this->assertSame(4_200, $service->latestFor($material, $provinsi)->price_per_kg);
        $this->assertSame(12_000, $listing->fresh()->price);
        $this->assertSame(50_000, $listingLain->fresh()->price);
        $this->assertEquals($listingSebelum, DB::table('listings')->orderBy('id')->get());
    }
}
