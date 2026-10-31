<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_returns_successful_response(): void
    {
        Listing::factory()->create();

        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Katalog Sirkula');
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

    public function test_catalog_can_be_filtered_by_search_keyword(): void
    {
        Listing::factory()->create([
            'title' => 'Botol PET Terpilah',
        ]);

        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $response = $this->get(route('catalog.index', [
            'search' => 'Botol PET',
        ]));

        $response->assertOk();
        $response->assertSee('Botol PET Terpilah');
        $response->assertDontSee('Sepeda Lipat Bekas');
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

    public function test_guest_can_view_catalog_listing_detail(): void
    {
        $listing = Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $response = $this->get(route('catalog.show', [
            'id' => $listing->id,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat Bekas');
        $response->assertSee('Informasi produk');
    }

    public function test_authenticated_user_can_view_catalog_listing_detail(): void
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
    }

    public function test_catalog_show_returns_404_for_unknown_listing(): void
    {
        $response = $this->get(route('catalog.show', [
            'id' => 999999,
        ]));

        $response->assertNotFound();
    }
}
