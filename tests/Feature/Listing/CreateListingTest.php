<?php

namespace Tests\Feature\Listing;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateListingTest extends TestCase
{
    use RefreshDatabase;

    private User $warga;

    private Region $region;

    private Hub $hub;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->warga = User::factory()->create();
        $this->region = Region::create(['name' => 'Bandung', 'code' => 'BDG']);
        $this->hub = $this->buatHub($this->region);
        $this->category = Category::factory()->forTypes(ListingType::Preloved)->create();
    }

    private function buatHub(Region $region, array $attributes = []): Hub
    {
        return Hub::create(array_merge([
            'name' => 'Hub '.$region->name,
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Contoh No. 1',
            'description' => 'Hub pengelolaan sampah',
            'status' => 'ACTIVE',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'tipe' => 'PRELOVED',
            'judul' => 'Sepeda Lipat Bekas',
            'kategori_id' => $this->category->id,
            'kondisi' => 'Baik',
            'harga' => 850000,
            'stok' => 1,
            'wilayah_id' => $this->region->id,
            'hub_id' => $this->hub->id,
            'deskripsi' => 'Masih mulus, ada lecet kecil di rangka.',
            'action' => 'submit',
            'fotos' => [UploadedFile::fake()->image('foto1.jpg')],
        ], $override);
    }

    public function test_halaman_create_hanya_menampilkan_kategori_aktif_untuk_preloved(): void
    {
        $nonaktif = Category::factory()->inactive()->forTypes(ListingType::Preloved)->create(['name' => 'Kategori Nonaktif']);
        $bukanPreloved = Category::factory()->forTypes(ListingType::Recyclable)->create(['name' => 'Khusus Daur Ulang']);

        $this->actingAs($this->warga)
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee($this->category->name)
            ->assertDontSee($nonaktif->name)
            ->assertDontSee($bukanPreloved->name)
            ->assertDontSee('Material Daur Ulang')
            ->assertDontSee('Produk Upcycle');
    }

    public function test_warga_dapat_menyimpan_listing_sebagai_draf(): void
    {
        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload([
                'action' => 'draft',
                'fotos' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.png'),
                ],
            ]))
            ->assertRedirect(route('listings.create'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $listing = Listing::firstOrFail();
        $this->assertSame('DRAFT', $listing->review_status);
        $this->assertSame($this->warga->id, $listing->user_id);
        $this->assertSame(850000, (int) $listing->price);
        $this->assertSame('Baik', $listing->item_condition);
        $this->assertCount(2, $listing->images);
        $this->assertTrue((bool) $listing->images->first()->is_primary);

        foreach ($listing->images as $image) {
            $this->assertTrue(Storage::disk('public')->exists($image->path_foto));
        }
    }

    public function test_warga_dapat_mengajukan_listing_untuk_diverifikasi(): void
    {
        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['action' => 'submit']))
            ->assertRedirect(route('listings.create'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('listings', [
            'title' => 'Sepeda Lipat Bekas',
            'review_status' => 'PENDING_REVIEW',
            'hub_id' => $this->hub->id,
            'region_id' => $this->region->id,
        ]);
    }

    public function test_foto_lebih_dari_lima_ditolak(): void
    {
        $fotos = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 6));

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['fotos' => $fotos]))
            ->assertSessionHasErrors('fotos');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_foto_lebih_dari_2_mb_ditolak(): void
    {
        $besar = UploadedFile::fake()->image('besar.jpg')->size(3000);

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['fotos' => [$besar]]))
            ->assertSessionHasErrors('fotos.0');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_hub_dari_wilayah_lain_ditolak(): void
    {
        $wilayahLain = Region::create(['name' => 'Garut', 'code' => 'GRT']);
        $hubLain = $this->buatHub($wilayahLain);

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['hub_id' => $hubLain->id]))
            ->assertSessionHasErrors('hub_id');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_hub_nonaktif_ditolak(): void
    {
        $arsip = $this->buatHub($this->region, ['name' => 'Hub Arsip', 'status' => 'ARCHIVED']);

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['hub_id' => $arsip->id]))
            ->assertSessionHasErrors('hub_id');
    }

    public function test_kategori_nonaktif_ditolak(): void
    {
        $nonaktif = Category::factory()->inactive()->forTypes(ListingType::Preloved)->create();

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['kategori_id' => $nonaktif->id]))
            ->assertSessionHasErrors('kategori_id');
    }

    public function test_kategori_yang_tidak_berlaku_untuk_preloved_ditolak(): void
    {
        $daurUlang = Category::factory()->forTypes(ListingType::Recyclable)->create();

        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['kategori_id' => $daurUlang->id]))
            ->assertSessionHasErrors('kategori_id');
    }

    public function test_kondisi_di_luar_opsi_ditolak(): void
    {
        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['kondisi' => 'Rusak berat']))
            ->assertSessionHasErrors('kondisi');
    }

    public function test_harga_desimal_ditolak(): void
    {
        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['harga' => '1000.75']))
            ->assertSessionHasErrors('harga');
    }

    public function test_tipe_selain_preloved_ditolak(): void
    {
        $this->actingAs($this->warga)
            ->post(route('listings.store'), $this->payload(['tipe' => 'RECYCLABLE']))
            ->assertSessionHasErrors('tipe');
    }

    public function test_admin_tidak_dapat_membuat_listing(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('listings.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('listings.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_tamu_diarahkan_untuk_login(): void
    {
        $this->get(route('listings.create'))->assertRedirect();
        $this->post(route('listings.store'), $this->payload())->assertRedirect();

        $this->assertDatabaseCount('listings', 0);
    }
}
