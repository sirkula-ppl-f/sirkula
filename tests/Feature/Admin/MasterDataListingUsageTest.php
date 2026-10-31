<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Material;
use App\Models\User;
use App\Rules\ActiveCategoryForType;
use App\Rules\ActiveMaterial;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Data master dengan tabel listings asli (saat ini stub PBI-11, nanti migrasi PBI-07/08).
 *
 * Test ini sengaja tidak memakai TabelPemakaianSementara, jadi ikut menguji kontrak tabel
 * listings: kolom category_id, material_id, listing_type, dan FK restrictOnDelete.
 */
class MasterDataListingUsageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function jumlahQuery(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get($url)->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    /**
     * Aturan seperti yang dipakai FormRequest listing baru (PBI-07/08).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, list<string>>
     */
    private function validasiListingBaru(array $data, ListingType $type = ListingType::Preloved): array
    {
        return Validator::make($data, [
            'category_id' => ['required', 'integer', new ActiveCategoryForType($type)],
            'material_id' => ['required', 'integer', new ActiveMaterial],
        ])->errors()->toArray();
    }

    public function test_kategori_yang_dipakai_listing_tampil_dipakai_tanpa_tombol_hapus(): void
    {
        $dipakai = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
        Category::factory()->create(['name' => 'Buku', 'slug' => 'buku']);
        Listing::factory()->create(['category_id' => $dipakai->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['dusk="category-row-buku"', '>Tidak</td>', 'dusk="category-row-elektronik"', '>Ya</td>'], false)
            ->assertSee('dusk="delete-buku"', false)
            ->assertDontSee('dusk="delete-elektronik"', false)
            ->assertSee('dusk="deactivate-elektronik"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $dipakai))
            ->assertOk()
            ->assertSee('Masih dipakai listing: Barang Bekas. Tipe ini tidak dapat dihapus dari kategori.');
    }

    public function test_hapus_kategori_yang_dipakai_listing_ditolak_dan_data_tetap_ada(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $listing = Listing::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.categories.index'))
            ->followingRedirects()
            ->delete(route('admin.categories.destroy', $category))
            ->assertOk()
            ->assertSee('Kategori sudah dipakai listing; hanya dapat dinonaktifkan.')
            ->assertDontSee('berhasil dihapus');

        $this->assertModelExists($category);
        $this->assertCount(count(ListingType::cases()), $category->fresh()->listingTypes());
        $this->assertSame($category->id, $listing->fresh()->category_id);
    }

    public function test_kategori_yang_dipakai_listing_tetap_bisa_dinonaktifkan_dan_listing_tidak_berubah(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $listing = Listing::factory()->create(['category_id' => $category->id]);
        $sebelum = $listing->fresh()->getAttributes();

        // Bila listing ikut tersentuh, updated_at-nya pasti berbeda.
        $this->travel(1)->minutes();

        $this->actingAs($this->admin)
            ->from(route('admin.categories.index'))
            ->patch(route('admin.categories.deactivate', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'Kategori "Elektronik" dinonaktifkan. Listing baru tidak dapat memakainya.');

        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame($sebelum, $listing->fresh()->getAttributes());
    }

    public function test_material_yang_dipakai_listing_tampil_dipakai_tanpa_tombol_hapus(): void
    {
        $dipakai = Material::factory()->create(['name' => 'Logam', 'slug' => 'logam']);
        Material::factory()->create(['name' => 'Kaca', 'slug' => 'kaca']);
        Listing::factory()->create(['material_id' => $dipakai->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.materials.index'))
            ->assertOk()
            ->assertSeeInOrder(['dusk="material-row-kaca"', '>Tidak</td>', 'dusk="material-row-logam"', '>Ya</td>'], false)
            ->assertSee('dusk="delete-kaca"', false)
            ->assertDontSee('dusk="delete-logam"', false)
            ->assertSee('dusk="deactivate-logam"', false);
    }

    public function test_hapus_material_yang_dipakai_listing_ditolak_dan_data_tetap_ada(): void
    {
        $material = Material::factory()->create(['name' => 'Logam']);
        $listing = Listing::factory()->create(['material_id' => $material->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.materials.index'))
            ->followingRedirects()
            ->delete(route('admin.materials.destroy', $material))
            ->assertOk()
            ->assertSee('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.')
            ->assertDontSee('berhasil dihapus');

        $this->assertModelExists($material);
        $this->assertSame($material->id, $listing->fresh()->material_id);
    }

    public function test_material_yang_dipakai_listing_tetap_bisa_dinonaktifkan_dan_listing_tidak_berubah(): void
    {
        $material = Material::factory()->create(['name' => 'Logam']);
        $listing = Listing::factory()->create(['material_id' => $material->id]);
        $sebelum = $listing->fresh()->getAttributes();

        // Bila listing ikut tersentuh, updated_at-nya pasti berbeda.
        $this->travel(1)->minutes();

        $this->actingAs($this->admin)
            ->from(route('admin.materials.index'))
            ->patch(route('admin.materials.deactivate', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('status', 'Material "Logam" dinonaktifkan. Listing baru tidak dapat memakainya.');

        $this->assertFalse($material->fresh()->is_active);
        $this->assertSame($sebelum, $listing->fresh()->getAttributes());
    }

    public function test_fk_listings_menolak_hapus_kategori_dan_material_langsung_di_database(): void
    {
        $category = Category::factory()->create();
        $material = Material::factory()->create();
        Listing::factory()->create(['category_id' => $category->id, 'material_id' => $material->id]);

        foreach ([$category, $material] as $model) {
            try {
                $model->delete();
                $this->fail(class_basename($model).' yang dipakai listing harus ditolak FK listings (restrictOnDelete).');
            } catch (QueryException $e) {
                $this->assertSame('23000', $e->getCode());
            }

            $this->assertModelExists($model);
        }
    }

    public function test_kategori_dan_material_nonaktif_ditolak_untuk_listing_baru_tetapi_listing_lama_tetap(): void
    {
        $category = Category::factory()->create();
        $material = Material::factory()->create();
        $listing = Listing::factory()->create(['category_id' => $category->id, 'material_id' => $material->id]);
        $data = ['category_id' => $category->id, 'material_id' => $material->id];

        $this->assertSame([], $this->validasiListingBaru($data));

        $this->actingAs($this->admin)->patch(route('admin.categories.deactivate', $category))->assertSessionHas('status');
        $this->actingAs($this->admin)->patch(route('admin.materials.deactivate', $material))->assertSessionHas('status');

        $this->assertSame([
            'category_id' => ['Kategori yang dipilih sudah nonaktif dan tidak dapat dipakai untuk listing baru.'],
            'material_id' => ['Material yang dipilih sudah nonaktif dan tidak dapat dipakai untuk listing baru.'],
        ], $this->validasiListingBaru($data));

        $this->assertNotContains($category->id, Category::active()->forListingType(ListingType::Preloved)->pluck('id')->all());
        $this->assertNotContains($material->id, Material::active()->pluck('id')->all());

        $listing->refresh();
        $this->assertSame($category->id, $listing->category_id);
        $this->assertSame($material->id, $listing->material_id);
    }

    public function test_kategori_ditolak_untuk_tipe_listing_yang_tidak_berlaku(): void
    {
        $material = Material::factory()->create();
        $daurUlang = Category::factory()->forTypes(ListingType::Recyclable)->create();
        $data = ['category_id' => $daurUlang->id, 'material_id' => $material->id];

        $this->assertSame(
            ['category_id' => ['Kategori yang dipilih tidak berlaku untuk Barang Bekas.']],
            $this->validasiListingBaru($data, ListingType::Preloved)
        );
        $this->assertSame([], $this->validasiListingBaru($data, ListingType::Recyclable));

        // Admin membuang tipe yang belum dipakai; tipe yang dipakai listing tetap berlaku.
        $pakaian = Category::factory()->create(['name' => 'Pakaian']);
        Listing::factory()->create(['category_id' => $pakaian->id, 'listing_type' => ListingType::Preloved->value]);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $pakaian), ['name' => 'Pakaian', 'listing_types' => ['PRELOVED']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.categories.index'));

        $data = ['category_id' => $pakaian->id, 'material_id' => $material->id];

        $this->assertSame([], $this->validasiListingBaru($data, ListingType::Preloved));
        $this->assertSame(
            ['category_id' => ['Kategori yang dipilih tidak berlaku untuk Produk Upcycle.']],
            $this->validasiListingBaru($data, ListingType::Upcycle)
        );
    }

    public function test_mengaktifkan_lagi_kategori_dan_material_membuatnya_bisa_dipilih_lagi(): void
    {
        $category = Category::factory()->inactive()->create();
        $material = Material::factory()->inactive()->create();
        $data = ['category_id' => $category->id, 'material_id' => $material->id];

        $this->assertSame(['category_id', 'material_id'], array_keys($this->validasiListingBaru($data)));

        $this->actingAs($this->admin)->patch(route('admin.categories.activate', $category))->assertSessionHas('status');
        $this->actingAs($this->admin)->patch(route('admin.materials.activate', $material))->assertSessionHas('status');

        $this->assertSame([], $this->validasiListingBaru($data));
        $this->assertContains($category->id, Category::active()->forListingType(ListingType::Preloved)->pluck('id')->all());
        $this->assertContains($material->id, Material::active()->pluck('id')->all());
    }

    public function test_kolom_dipakai_di_index_kategori_tidak_menambah_query_per_baris(): void
    {
        $url = route('admin.categories.index');
        $categories = Category::factory()->count(2)->create();
        Listing::factory()->create(['category_id' => $categories[0]->id]);

        $this->jumlahQuery($url);
        $denganDuaKategori = $this->jumlahQuery($url);

        foreach (Category::factory()->count(13)->create()->take(5) as $category) {
            Listing::factory()->create(['category_id' => $category->id]);
        }

        $this->assertSame($denganDuaKategori, $this->jumlahQuery($url), 'Jumlah query halaman daftar harus sama untuk 2 dan 15 kategori.');
    }

    public function test_kolom_dipakai_di_index_material_tidak_menambah_query_per_baris(): void
    {
        $url = route('admin.materials.index');
        $materials = Material::factory()->count(2)->create();
        Listing::factory()->create(['material_id' => $materials[0]->id]);

        $this->jumlahQuery($url);
        $denganDuaMaterial = $this->jumlahQuery($url);

        foreach (Material::factory()->count(13)->create()->take(5) as $material) {
            Listing::factory()->create(['material_id' => $material->id]);
        }

        $this->assertSame($denganDuaMaterial, $this->jumlahQuery($url), 'Jumlah query halaman daftar harus sama untuk 2 dan 15 material.');
    }
}
