<?php

namespace Tests\Browser;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CatalogTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_guest_can_open_catalog(): void
    {
        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(route('catalog.index'))
                ->assertSee('Katalog Sirkula')
                ->assertSee('Sepeda Lipat Bekas')
                ->assertSee('Masuk')
                ->assertSee('Daftar');
        });
    }

    public function test_guest_can_filter_catalog_by_listing_type_and_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Olahraga dan Hobi',
        ]);

        Listing::factory()->create([
            'title' => 'Botol PET Terpilah',
            'listing_type' => ListingType::Recyclable->value,
            'category_id' => $category->id,
        ]);

        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
            'listing_type' => ListingType::Preloved->value,
        ]);

        $this->browse(function (Browser $browser) use ($category) {
            $browser->visit(route('catalog.index'))
                ->clickLink(ListingType::Recyclable->label())
                ->assertSee('Botol PET Terpilah')
                ->assertDontSee('Sepeda Lipat Bekas')
                ->select('category_id', (string) $category->id)
                ->press('Terapkan')
                ->assertSee('Botol PET Terpilah')
                ->assertDontSee('Sepeda Lipat Bekas');
        });
    }

    public function test_guest_can_open_listing_detail_from_catalog(): void
    {
        Listing::factory()->create([
            'title' => 'Sepeda Lipat Bekas',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(route('catalog.index'))
                ->assertSee('Sepeda Lipat Bekas')
                ->clickLink('Sepeda Lipat Bekas')
                ->assertSee('Informasi produk')
                ->assertSee('Sepeda Lipat Bekas')
                ->assertSee('Stok tersedia');
        });
    }
}
