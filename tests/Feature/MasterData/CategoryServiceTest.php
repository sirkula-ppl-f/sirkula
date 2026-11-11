<?php

namespace Tests\Feature\MasterData;

use App\Enums\ListingType;
use App\Exceptions\MasterDataInUseException;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CategoryService::class);
    }

    /**
     * @param  list<ListingType>  $types
     */
    private function buat(string $name, array $types = [ListingType::Preloved]): Category
    {
        return $this->service->create(['name' => $name, 'description' => null, 'listing_types' => $types]);
    }

    public function test_membuat_kategori_dengan_slug_dan_tipe_listing(): void
    {
        $category = $this->service->create([
            'name' => 'Perabot Rumah Tangga',
            'description' => 'Meja dan kursi.',
            'listing_types' => ['UPCYCLE', 'PRELOVED', 'PRELOVED'],
        ]);

        $this->assertSame('perabot-rumah-tangga', $category->slug);
        $this->assertSame('Meja dan kursi.', $category->description);
        $this->assertTrue($category->is_active);
        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->fresh()->listingTypes()->all());
    }

    public function test_slug_yang_bentrok_ditambah_angka_berurutan(): void
    {
        $this->assertSame('elektronik', $this->buat('Elektronik')->slug);
        $this->assertSame('elektronik-2', $this->buat('Elektronik!')->slug);
        $this->assertSame('elektronik-3', $this->buat('Elektronik?')->slug);
    }

    public function test_slug_memakai_cadangan_bila_nama_hanya_berisi_simbol(): void
    {
        $this->assertSame('kategori', $this->buat('!!!')->slug);
        $this->assertSame('kategori-2', $this->buat('???')->slug);
    }

    public function test_slug_tidak_berubah_saat_nama_diubah(): void
    {
        $category = $this->buat('Elektronik');

        $this->service->update($category, ['name' => 'Elektronik dan Gawai', 'description' => null, 'listing_types' => ['PRELOVED']]);

        $category->refresh();
        $this->assertSame('Elektronik dan Gawai', $category->name);
        $this->assertSame('elektronik', $category->slug);
    }

    public function test_ubah_tipe_listing_menambah_dan_menghapus_tipe(): void
    {
        $category = $this->buat('Tas', [ListingType::Preloved, ListingType::Recyclable]);

        $this->service->update($category, ['name' => 'Tas', 'description' => null, 'listing_types' => ['RECYCLABLE', 'UPCYCLE']]);

        $this->assertSame([ListingType::Recyclable, ListingType::Upcycle], $category->fresh()->listingTypes()->all());
        $this->assertSame([ListingType::Recyclable, ListingType::Upcycle], $category->listingTypes()->all(), 'Relasi lama tidak boleh tertinggal di model.');
    }

    public function test_menghapus_tipe_listing_yang_dipakai_ditolak_dan_data_tidak_berubah(): void
    {
        $category = $this->buat('Pakaian', [ListingType::Preloved, ListingType::Upcycle]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);

        try {
            $this->service->update($category, ['name' => 'Pakaian Baru', 'description' => null, 'listing_types' => ['PRELOVED']]);
            $this->fail('Seharusnya ditolak karena tipe Produk Upcycle masih dipakai.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['listing_types' => ['Tipe listing Produk Upcycle masih dipakai listing dengan kategori ini, jadi tidak dapat dihapus.']],
                $e->errors()
            );
        }

        $category = Category::find($category->id);
        $this->assertSame('Pakaian', $category->name);
        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->listingTypes()->all());
    }

    public function test_pesan_menyebut_semua_tipe_yang_dipakai(): void
    {
        $category = $this->buat('Pakaian', ListingType::cases());
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);

        $this->expectExceptionMessage('Tipe listing Barang Bekas dan Produk Upcycle masih dipakai');

        $this->service->update($category, ['name' => 'Pakaian', 'description' => null, 'listing_types' => ['RECYCLABLE']]);
    }

    public function test_tipe_yang_dipakai_boleh_dipertahankan_sambil_menambah_tipe_lain(): void
    {
        $category = $this->buat('Pakaian', [ListingType::Upcycle]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);

        $this->service->update($category, ['name' => 'Pakaian', 'description' => null, 'listing_types' => ['UPCYCLE', 'PRELOVED']]);

        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->fresh()->listingTypes()->all());
    }

    public function test_hapus_kategori_yang_dipakai_ditolak(): void
    {
        $category = $this->buat('Elektronik');
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);

        $this->expectException(MasterDataInUseException::class);
        $this->expectExceptionMessage('Kategori sudah dipakai listing; hanya dapat dinonaktifkan.');

        try {
            $this->service->delete($category);
        } finally {
            $this->assertModelExists($category);
        }
    }

    public function test_hapus_kategori_yang_belum_dipakai_ikut_menghapus_tipe_listingnya(): void
    {
        $category = $this->buat('Elektronik', ListingType::cases());
        $lain = $this->buat('Buku');

        $this->service->delete($category);

        $this->assertModelMissing($category);
        $this->assertDatabaseMissing('category_listing_types', ['category_id' => $category->id]);
        $this->assertSame([ListingType::Preloved], $lain->fresh()->listingTypes()->all());
    }

    public function test_penolakan_foreign_key_saat_hapus_dilaporkan_sebagai_sudah_dipakai(): void
    {
        $category = $this->buat('Elektronik');
        $this->buatTabelSementara('contoh_pemakaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
        });
        DB::table('contoh_pemakaian')->insert(['category_id' => $category->id]);

        $this->expectException(MasterDataInUseException::class);
        $this->expectExceptionMessage('Kategori sudah dipakai listing; hanya dapat dinonaktifkan.');

        $this->service->delete($category);
    }

    public function test_nonaktif_dan_aktif_hanya_mengubah_status(): void
    {
        $category = $this->buat('Elektronik', [ListingType::Preloved, ListingType::Upcycle]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);
        $listingSebelum = DB::table('listings')->get();

        $this->service->deactivate($category);

        $this->assertFalse($category->fresh()->is_active);
        $this->assertEquals($listingSebelum, DB::table('listings')->get());
        $this->assertSame('Elektronik', $category->fresh()->name);
        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->fresh()->listingTypes()->all());

        $this->service->activate($category);

        $this->assertTrue($category->fresh()->is_active);
        $this->assertEquals($listingSebelum, DB::table('listings')->get());
    }

    public function test_pencarian_memperlakukan_persen_garis_bawah_dan_seru_sebagai_huruf_biasa(): void
    {
        foreach (['Diskon 50%', 'Diskon 500', 'Kode_A', 'KodeXA', 'Seru!', 'Seru Sekali'] as $name) {
            $this->buat($name);
        }

        $cari = fn (string $term) => $this->service->paginate(['search' => $term])->pluck('name')->all();

        $this->assertSame(['Diskon 50%'], $cari('50%'));
        $this->assertSame(['Kode_A'], $cari('_'));
        $this->assertSame(['Seru!'], $cari('!'));
        $this->assertSame(['Diskon 50%', 'Diskon 500'], $cari('diskon'), 'Pencarian tidak membedakan huruf kapital.');
    }

    public function test_filter_status_dan_tipe_listing(): void
    {
        $aktifPreloved = Category::factory()->forTypes(ListingType::Preloved)->create(['name' => 'A Aktif Preloved']);
        $nonaktifPreloved = Category::factory()->inactive()->forTypes(ListingType::Preloved)->create(['name' => 'B Nonaktif Preloved']);
        $aktifRecyclable = Category::factory()->forTypes(ListingType::Recyclable)->create(['name' => 'C Aktif Recyclable']);

        $ids = fn (array $filters) => $this->service->paginate($filters)->pluck('id')->all();

        $this->assertSame([$aktifPreloved->id, $aktifRecyclable->id], $ids(['active' => true]));
        $this->assertSame([$nonaktifPreloved->id], $ids(['active' => false]));
        $this->assertSame([$aktifPreloved->id, $nonaktifPreloved->id], $ids(['type' => ListingType::Preloved]));
        $this->assertSame([$aktifPreloved->id], $ids(['active' => true, 'type' => ListingType::Preloved]));
        $this->assertCount(3, $ids([]));
    }

    public function test_daftar_15_per_halaman_urut_nama_dengan_status_dipakai(): void
    {
        Category::factory()->count(16)->create();
        $dipakai = Category::orderBy('name')->first();
        $this->tambahListing(['category_id' => $dipakai->id, 'listing_type' => ListingType::Preloved->value]);

        $page = $this->service->paginate();

        $this->assertSame(15, $page->perPage());
        $this->assertSame(16, $page->total());
        $this->assertCount(15, $page->items());
        $this->assertSame($dipakai->id, $page->first()->id);
        $this->assertTrue($page->first()->isInUse());
        $this->assertFalse($page->last()->isInUse());
        $this->assertTrue($page->first()->relationLoaded('categoryListingTypes'));
    }
}
