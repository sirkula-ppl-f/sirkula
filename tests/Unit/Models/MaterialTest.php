<?php

namespace Tests\Unit\Models;

use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

    public function test_tabel_material_tidak_punya_kolom_wilayah(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at'],
            Schema::getColumnListing('materials')
        );
    }

    public function test_scope_active_hanya_mengambil_material_aktif(): void
    {
        $aktif = Material::factory()->create();
        $nonaktif = Material::factory()->inactive()->create();

        $this->assertTrue($aktif->is_active);
        $this->assertFalse($nonaktif->is_active);
        $this->assertSame([$aktif->id], Material::active()->pluck('id')->all());
    }

    public function test_belum_dipakai_bila_tabel_listings_dan_reference_prices_belum_ada(): void
    {
        $this->lewatiBilaTabelSudahAda('listings');
        $this->lewatiBilaTabelSudahAda('reference_prices');

        $material = Material::factory()->create();

        $this->assertFalse($material->isInUse());
        $this->assertFalse(Material::withInUse()->sole()->isInUse());
    }

    public function test_dipakai_bila_ada_listing_dengan_material_id_ini(): void
    {
        $dipakai = Material::factory()->create();
        $lain = Material::factory()->create();
        $this->buatTabelListings();

        $this->assertFalse($dipakai->isInUse(), 'Tabel listings kosong berarti belum dipakai.');

        $this->tambahListing(['material_id' => $dipakai->id]);

        $this->assertTrue($dipakai->isInUse());
        $this->assertFalse($lain->isInUse());
    }

    public function test_dipakai_bila_ada_harga_acuan_dengan_material_id_ini(): void
    {
        $dipakai = Material::factory()->create();
        $lain = Material::factory()->create();

        $this->tambahHargaAcuan($dipakai->id);

        $this->assertTrue($dipakai->isInUse());
        $this->assertFalse($lain->isInUse());
    }

    public function test_with_in_use_memeriksa_listings_dan_reference_prices(): void
    {
        $diListing = Material::factory()->create();
        $diHargaAcuan = Material::factory()->create();
        $belumDipakai = Material::factory()->create();
        $this->tambahListing(['material_id' => $diListing->id]);
        $this->tambahHargaAcuan($diHargaAcuan->id);

        $materials = Material::withInUse()->get()->keyBy('id');

        $this->assertTrue($materials[$diListing->id]->isInUse());
        $this->assertTrue($materials[$diHargaAcuan->id]->isInUse());
        $this->assertFalse($materials[$belumDipakai->id]->isInUse());
    }
}
