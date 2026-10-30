<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_returns_successful_response(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Katalog Sirkula');
    }

    public function test_catalog_can_be_filtered_by_listing_type(): void
    {
        $response = $this->get(route('catalog.index', [
            'type' => 'Material Daur Ulang',
        ]));

        $response->assertOk();
        $response->assertSee('Botol PET Terpilah');
        $response->assertSee('Kardus Bekas');
        $response->assertSee('Kaleng Aluminium');
        $response->assertDontSee('Sepeda Lipat Bekas');
        $response->assertDontSee('Kipas Angin Meja');
    }

    public function test_catalog_can_be_filtered_by_search_keyword(): void
    {
        $response = $this->get(route('catalog.index', [
            'search' => 'Botol PET',
        ]));

        $response->assertOk();
        $response->assertSee('Botol PET Terpilah');
        $response->assertDontSee('Sepeda Lipat Bekas');
    }

    public function test_catalog_can_be_filtered_by_region(): void
    {
        $response = $this->get(route('catalog.index', [
            'region' => 'Jawa Barat',
        ]));

        $response->assertOk();
        $response->assertSee('Botol PET Terpilah');
        $response->assertDontSee('Sepeda Lipat Bekas');
    }

    public function test_guest_can_view_catalog_listing_detail(): void
    {
        $response = $this->get(route('catalog.show', [
            'id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat Bekas');
        $response->assertSee('Olahraga dan Hobi');
        $response->assertSee('Deskripsi produk');
    }

    public function test_authenticated_user_can_view_catalog_listing_detail(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('catalog.show', [
            'id' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Sepeda Lipat Bekas');
        $response->assertSee('Olahraga dan Hobi');
    }

    public function test_catalog_show_returns_404_for_unknown_listing(): void
    {
        $response = $this->get(route('catalog.show', [
            'id' => 999999,
        ]));

        $response->assertNotFound();
    }
}
