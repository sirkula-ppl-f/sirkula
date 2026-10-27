<?php

namespace Tests\Unit\Models;

use App\Enums\ListingType;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

    public function test_tabel_kategori_tidak_punya_kolom_wilayah(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at'],
            Schema::getColumnListing('categories')
        );
    }

    public function test_factory_default_berlaku_untuk_semua_tipe_listing(): void
    {
        $category = Category::factory()->create();

        $this->assertSame(ListingType::cases(), $category->listingTypes()->all());
        $this->assertTrue($category->is_active);
    }

    public function test_for_types_mengganti_tipe_listing_default(): void
    {
        $category = Category::factory()->forTypes(ListingType::Recyclable)->create();

        $this->assertSame([ListingType::Recyclable], $category->listingTypes()->all());
        $this->assertDatabaseCount('category_listing_types', 1);
    }

    public function test_tipe_listing_urut_sesuai_enum(): void
    {
        $category = Category::factory()->forTypes(ListingType::Upcycle, ListingType::Preloved)->create();

        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->listingTypes()->all());
    }

    public function test_scope_active_hanya_mengambil_kategori_aktif(): void
    {
        $aktif = Category::factory()->create();
        Category::factory()->inactive()->create();

        $this->assertSame([$aktif->id], Category::active()->pluck('id')->all());
    }

    public function test_scope_for_listing_type_hanya_mengambil_kategori_bertipe_itu(): void
    {
        $preloved = Category::factory()->forTypes(ListingType::Preloved)->create();
        $semua = Category::factory()->create();
        Category::factory()->forTypes(ListingType::Recyclable)->create();

        $this->assertEqualsCanonicalizing(
            [$preloved->id, $semua->id],
            Category::forListingType(ListingType::Preloved)->pluck('id')->all()
        );
    }

    public function test_scope_active_dan_for_listing_type_bisa_digabung(): void
    {
        $dipilih = Category::factory()->forTypes(ListingType::Upcycle)->create();
        Category::factory()->inactive()->forTypes(ListingType::Upcycle)->create();
        Category::factory()->forTypes(ListingType::Preloved)->create();

        $this->assertSame(
            [$dipilih->id],
            Category::active()->forListingType(ListingType::Upcycle)->pluck('id')->all()
        );
    }

    public function test_belum_dipakai_bila_tabel_listings_belum_ada(): void
    {
        $this->lewatiBilaTabelSudahAda('listings');

        $category = Category::factory()->create();

        $this->assertFalse($category->isInUse());
        $this->assertFalse(Category::withInUse()->sole()->isInUse());
        $this->assertTrue($category->listingTypesInUse()->isEmpty());
    }

    public function test_dipakai_bila_ada_listing_dengan_category_id_ini(): void
    {
        $dipakai = Category::factory()->create();
        $lain = Category::factory()->create();
        $this->buatTabelListings();

        $this->assertFalse($dipakai->isInUse(), 'Tabel listings kosong berarti belum dipakai.');

        $this->tambahListing(['category_id' => $dipakai->id, 'listing_type' => ListingType::Preloved->value]);

        $this->assertTrue($dipakai->isInUse());
        $this->assertFalse($lain->isInUse());
    }

    public function test_with_in_use_menandai_kategori_yang_dipakai_tanpa_query_per_baris(): void
    {
        $dipakai = Category::factory()->create();
        $lain = Category::factory()->create();
        $this->tambahListing(['category_id' => $dipakai->id, 'listing_type' => ListingType::Preloved->value]);

        $categories = Category::withInUse()->get()->keyBy('id');

        DB::enableQueryLog();
        $this->assertTrue($categories[$dipakai->id]->isInUse());
        $this->assertFalse($categories[$lain->id]->isInUse());
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_tipe_listing_yang_dipakai_dibaca_dari_kolom_listing_type(): void
    {
        $category = Category::factory()->create();
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);

        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->listingTypesInUse()->all());
    }

    public function test_semua_tipe_dianggap_dipakai_bila_listings_belum_punya_kolom_listing_type(): void
    {
        $this->lewatiBilaTabelSudahAda('listings');

        $dipakai = Category::factory()->forTypes(ListingType::Preloved, ListingType::Upcycle)->create();
        $belumDipakai = Category::factory()->create();
        $this->buatTabelListings(denganListingType: false);
        DB::table('listings')->insert(['category_id' => $dipakai->id]);

        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $dipakai->listingTypesInUse()->all());
        $this->assertTrue($belumDipakai->listingTypesInUse()->isEmpty());
    }

    public function test_tipe_listing_ikut_terhapus_saat_kategori_dihapus(): void
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertDatabaseCount('category_listing_types', 0);
    }
}
