<?php

namespace Tests\Feature\MasterData;

use App\Exceptions\MasterDataInUseException;
use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class MaterialServiceTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

    private MaterialService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MaterialService::class);
    }

    private function buat(string $name): Material
    {
        return $this->service->create(['name' => $name, 'description' => null]);
    }

    public function test_membuat_material_dengan_slug(): void
    {
        $material = $this->service->create([
            'name' => 'Plastik PET',
            'description' => 'Botol minuman bening.',
        ]);

        $this->assertSame('plastik-pet', $material->slug);
        $this->assertSame('Botol minuman bening.', $material->description);
        $this->assertTrue($material->fresh()->is_active);
    }

    public function test_slug_yang_bentrok_ditambah_angka_berurutan(): void
    {
        $this->assertSame('kertas', $this->buat('Kertas')->slug);
        $this->assertSame('kertas-2', $this->buat('Kertas!')->slug);
        $this->assertSame('kertas-3', $this->buat('Kertas?')->slug);
    }

    public function test_slug_memakai_cadangan_bila_nama_hanya_berisi_simbol(): void
    {
        $this->assertSame('material', $this->buat('!!!')->slug);
        $this->assertSame('material-2', $this->buat('???')->slug);
    }

    public function test_slug_tidak_berubah_saat_nama_diubah(): void
    {
        $material = $this->buat('Kertas');

        $this->service->update($material, ['name' => 'Kertas dan Kardus', 'description' => 'Kardus bekas.']);

        $material->refresh();
        $this->assertSame('Kertas dan Kardus', $material->name);
        $this->assertSame('Kardus bekas.', $material->description);
        $this->assertSame('kertas', $material->slug);
    }

    public function test_hapus_material_yang_dipakai_listing_ditolak(): void
    {
        $material = $this->buat('Logam');
        $this->tambahListing(['material_id' => $material->id]);

        $this->expectException(MasterDataInUseException::class);
        $this->expectExceptionMessage('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.');

        try {
            $this->service->delete($material);
        } finally {
            $this->assertModelExists($material);
        }
    }

    public function test_hapus_material_yang_dipakai_harga_acuan_ditolak(): void
    {
        $material = $this->buat('Logam');
        $this->tambahHargaAcuan($material->id);

        $this->expectException(MasterDataInUseException::class);
        $this->expectExceptionMessage('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.');

        try {
            $this->service->delete($material);
        } finally {
            $this->assertModelExists($material);
        }
    }

    public function test_hapus_material_yang_belum_dipakai(): void
    {
        $material = $this->buat('Logam');
        $lain = $this->buat('Kaca');
        $this->tambahListing(['material_id' => $lain->id]);

        $this->service->delete($material);

        $this->assertModelMissing($material);
        $this->assertModelExists($lain);
    }

    public function test_penolakan_foreign_key_saat_hapus_dilaporkan_sebagai_sudah_dipakai(): void
    {
        $material = $this->buat('Logam');
        $this->buatTabelSementara('contoh_pemakaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
        });
        DB::table('contoh_pemakaian')->insert(['material_id' => $material->id]);

        $this->expectException(MasterDataInUseException::class);
        $this->expectExceptionMessage('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.');

        $this->service->delete($material);
    }

    public function test_nonaktif_dan_aktif_hanya_mengubah_status(): void
    {
        $material = $this->service->create(['name' => 'Logam', 'description' => 'Kaleng dan besi.']);
        $this->tambahListing(['material_id' => $material->id]);
        $this->tambahHargaAcuan($material->id);
        $listingSebelum = DB::table('listings')->get();
        $hargaAcuanSebelum = DB::table('reference_prices')->get();

        $this->service->deactivate($material);

        $material->refresh();
        $this->assertFalse($material->is_active);
        $this->assertSame('Logam', $material->name);
        $this->assertSame('logam', $material->slug);
        $this->assertSame('Kaleng dan besi.', $material->description);
        $this->assertEquals($listingSebelum, DB::table('listings')->get());
        $this->assertEquals($hargaAcuanSebelum, DB::table('reference_prices')->get());

        $this->service->activate($material);

        $this->assertTrue($material->fresh()->is_active);
        $this->assertEquals($listingSebelum, DB::table('listings')->get());
        $this->assertEquals($hargaAcuanSebelum, DB::table('reference_prices')->get());
    }

    public function test_pencarian_memperlakukan_persen_garis_bawah_dan_seru_sebagai_huruf_biasa(): void
    {
        foreach (['Kadar 50%', 'Kadar 500', 'Kode_A', 'KodeXA', 'Seru!', 'Seru Sekali'] as $name) {
            $this->buat($name);
        }

        $cari = fn (string $term) => $this->service->paginate(['search' => $term])->pluck('name')->all();

        $this->assertSame(['Kadar 50%'], $cari('50%'));
        $this->assertSame(['Kode_A'], $cari('_'));
        $this->assertSame(['Seru!'], $cari('!'));
        $this->assertSame(['Kadar 50%', 'Kadar 500'], $cari('kadar'), 'Pencarian tidak membedakan huruf kapital.');
    }

    public function test_filter_status(): void
    {
        $aktif = Material::factory()->create(['name' => 'A Aktif']);
        $nonaktif = Material::factory()->inactive()->create(['name' => 'B Nonaktif']);

        $ids = fn (array $filters) => $this->service->paginate($filters)->pluck('id')->all();

        $this->assertSame([$aktif->id], $ids(['active' => true]));
        $this->assertSame([$nonaktif->id], $ids(['active' => false]));
        $this->assertSame([$aktif->id, $nonaktif->id], $ids([]));
    }

    public function test_daftar_15_per_halaman_urut_nama_dengan_status_dipakai_dari_listing_dan_harga_acuan(): void
    {
        Material::factory()->count(16)->create();
        [$diListing, $diHargaAcuan] = Material::orderBy('name')->take(2)->get();
        $this->tambahListing(['material_id' => $diListing->id]);
        $this->tambahHargaAcuan($diHargaAcuan->id);

        $page = $this->service->paginate();

        $this->assertSame(15, $page->perPage());
        $this->assertSame(16, $page->total());
        $this->assertCount(15, $page->items());
        $this->assertSame([$diListing->id, $diHargaAcuan->id], $page->take(2)->pluck('id')->all());
        $this->assertTrue($page[0]->isInUse());
        $this->assertTrue($page[1]->isInUse());
        $this->assertFalse($page->last()->isInUse());
        $this->assertArrayHasKey('in_use', $page->first()->getAttributes(), 'Status dipakai diambil lewat subquery, bukan query per baris.');
    }
}
