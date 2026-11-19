<?php

namespace Tests\Feature;

use App\Enums\ItemCondition;
use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_returns_successful_response(): void
    {
        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Katalog Sirkula');
        $response->assertSee('Sepeda Lipat Bekas');
    }

    public function test_catalog_link_appears_in_navbar_for_guest_warga_and_admin(): void
    {
        // Tamu
        $guestHtml = $this->get(route('catalog.index'))->assertOk()->getContent();
        $this->assertStringContainsString('dusk="nav-catalog"', $guestHtml);
        $this->assertStringContainsString('href="'.route('catalog.index').'"', $guestHtml);

        // Warga
        $warga = User::factory()->create();
        $wargaHtml = $this->actingAs($warga)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('dusk="nav-catalog"', $wargaHtml);

        // Admin
        $admin = User::factory()->admin()->create();
        $adminHtml = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('dusk="nav-catalog"', $adminHtml);
    }

    public function test_catalog_can_be_filtered_by_listing_type(): void
    {
        Listing::factory()->create([
            'title' => 'Botol PET Terpilah',
            'listing_type' => ListingType::Recyclable->value,
        ]);

        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
            'listing_type' => ListingType::Preloved->value,
        ]);

        $response = $this->get(route('catalog.index', [
            'type' => ListingType::Recyclable->value,
        ]));

        $response->assertOk();
        $response->assertSee('Botol PET Terpilah');
        $response->assertDontSee('Sepeda Lipat Bekas');
    }

    public function test_catalog_can_be_filtered_by_search_keyword_in_title_or_description(): void
    {
        Listing::factory()->create([
            'title' => 'Botol PET Terpilah',
            'description' => 'Label sudah dilepas dan bersih.',
        ]);

        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
            'description' => 'Rantai baru diganti.',
        ]);

        // Cari dari judul
        $this->get(route('catalog.index', ['search' => 'Botol PET']))
            ->assertOk()
            ->assertSee('Botol PET Terpilah')
            ->assertDontSee('Sepeda Lipat Bekas');

        // Cari dari deskripsi (FR-012)
        $this->get(route('catalog.index', ['search' => 'Rantai baru']))
            ->assertOk()
            ->assertSee('Sepeda Lipat Bekas')
            ->assertDontSee('Botol PET Terpilah');
    }

    public function test_catalog_can_be_filtered_by_region(): void
    {
        $region = Region::create([
            'name' => 'Jawa Barat',
            'code' => 'JBR',
            'parent_id' => null,
        ]);

        $user = User::factory()->create([
            'region_id' => $region->id,
        ]);

        Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Listing Jawa Barat',
        ]);

        Listing::factory()->create([
            'title' => 'Listing Wilayah Lain',
        ]);

        $response = $this->get(route('catalog.index', [
            'region_id' => $region->id,
        ]));

        $response->assertOk();
        $response->assertSee('Listing Jawa Barat');
        $response->assertDontSee('Listing Wilayah Lain');
    }

    public function test_catalog_can_be_filtered_by_category_id(): void
    {
        $category = Category::factory()->create([
            'name' => 'Olahraga dan Hobi',
        ]);

        Listing::factory()->create([
            'category_id' => $category->id,
            'title' => 'Sepeda Lipat',
        ]);

        Listing::factory()->create([
            'title' => 'Botol PET',
        ]);

        $response = $this->get(route('catalog.index', [
            'category_id' => $category->id,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat');
        $response->assertDontSee('Botol PET');
    }

    public function test_catalog_can_be_filtered_by_condition_and_hub_and_price_range(): void
    {
        $region = Region::create(['name' => 'Jawa Barat', 'code' => '32', 'parent_id' => null]);
        $hub = Hub::create([
            'name' => 'Hub RW 05 Dago',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Dago No. 5',
            'status' => 'ACTIVE',
        ]);

        Listing::factory()->create([
            'title' => 'Kipas Angin Mulus',
            'item_condition' => ItemCondition::SepertiBaru->value,
            'hub_id' => $hub->id,
            'price' => 120000,
        ]);

        Listing::factory()->create([
            'title' => 'Rak Buku Lama',
            'item_condition' => ItemCondition::Cukup->value,
            'price' => 300000,
        ]);

        $this->get(route('catalog.index', [
            'condition' => ItemCondition::SepertiBaru->value,
            'hub_id' => $hub->id,
            'min_price' => 100000,
            'max_price' => 150000,
        ]))
            ->assertOk()
            ->assertSee('Kipas Angin Mulus')
            ->assertDontSee('Rak Buku Lama');
    }

    public function test_catalog_hides_listings_with_archived_hub(): void
    {
        $region = Region::create(['name' => 'Jawa Barat', 'code' => '32', 'parent_id' => null]);
        $archivedHub = Hub::create([
            'name' => 'Hub Arsip',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Arsip',
            'status' => 'ARCHIVED',
        ]);

        Listing::factory()->create([
            'title' => 'Listing Hub Arsip',
            'hub_id' => $archivedHub->id,
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('Listing Hub Arsip');
    }

    public function test_guest_can_view_catalog_listing_detail(): void
    {
        $listing = Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
            'description' => 'Sepeda lipat 16 inci masih mulus.',
        ]);

        $response = $this->get(route('catalog.show', [
            'id' => $listing->id,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat Bekas');
        $response->assertSee('Informasi produk');
        $response->assertSee('Sepeda lipat 16 inci masih mulus.');
        $response->assertSee('Masuk untuk Membeli');
    }

    public function test_authenticated_user_can_view_catalog_listing_detail_and_add_to_cart_form(): void
    {
        $user = User::factory()->create();

        $listing = Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $response = $this->actingAs($user)->get(route('catalog.show', [
            'id' => $listing->id,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat Bekas');
        $response->assertSee('Tambah ke Keranjang');
        $response->assertSee('dusk="add-to-cart-button"', false);
    }

    public function test_detail_page_displays_reference_price_and_guide_tip_when_available(): void
    {
        $region = Region::create(['name' => 'Jawa Barat', 'code' => '32', 'parent_id' => null]);
        $material = Material::factory()->create(['name' => 'Plastik PET']);

        ReferencePrice::factory()
            ->forMaterial($material)
            ->forRegion($region)
            ->create([
                'price_per_kg' => 4500,
                'source' => 'Bank Sampah Induk',
            ]);

        Guide::factory()->forPlacements('detail_page')->create([
            'title' => 'Tips Membeli Material',
            'body' => 'Periksa satuan lot dan berat sebelum membeli.',
        ]);

        $listing = Listing::factory()->create([
            'title' => 'Botol PET Terpilah (lot 25 kg)',
            'listing_type' => ListingType::Recyclable->value,
            'material_id' => $material->id,
            'region_id' => $region->id,
            'sale_unit' => 'LOT',
        ]);

        $this->get(route('catalog.show', $listing->id))
            ->assertOk()
            ->assertSee('Rp 4.500/kg')
            ->assertSee('Bank Sampah Induk')
            ->assertSee('Tips Membeli Material');
    }

    public function test_catalog_show_returns_404_for_unknown_listing(): void
    {
        $response = $this->get(route('catalog.show', [
            'id' => 999999,
        ]));

        $response->assertNotFound();
    }
}
