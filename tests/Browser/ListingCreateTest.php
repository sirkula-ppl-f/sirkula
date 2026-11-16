<?php

namespace Tests\Browser;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ListingCreateTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_warga_dapat_membuat_listing_dan_mengajukannya(): void
    {
        $warga = User::factory()->create();
        $region = Region::create(['name' => 'Bandung', 'code' => 'BDG']);
        $hub = Hub::create([
            'name' => 'Hub Bandung',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Bandung No. 1',
            'description' => 'Hub pengelolaan sampah',
            'status' => 'ACTIVE',
        ]);
        $category = Category::factory()->forTypes(ListingType::Preloved)->create();

        $foto = base_path('tests/Browser/fixtures/foto.jpg');
        if (! is_dir(dirname($foto))) {
            mkdir(dirname($foto), 0777, true);
        }
        if (! file_exists($foto)) {
            $img = imagecreatetruecolor(200, 200);
            imagejpeg($img, $foto, 80);
        }

        $this->browse(function (Browser $browser) use ($warga, $region, $hub, $category, $foto) {
            $browser->loginAs($warga)
                ->visit('/listings/create')
                ->assertSee('Barang Bekas')
                ->assertDontSee('Material Daur Ulang')
                ->assertDontSee('Produk Upcycle')
                ->type('#judul', 'Sepeda Lipat Bekas')
                ->select('#kategori_id', (string) $category->id)
                ->select('#kondisi', 'Baik')
                ->type('#harga_tampil', '850000')
                ->type('#stok', '1')
                ->select('#wilayah_id', (string) $region->id)
                ->waitFor('#hub_id option[value="'.$hub->id.'"]')
                ->select('#hub_id', (string) $hub->id)
                ->type('#deskripsi', 'Masih mulus, ada lecet kecil di rangka.')
                ->script("document.getElementById('fotos').classList.remove('hidden')");
                $browser->attach('#fotos', $foto)
                    ->press('Ajukan ke Hub')
                    ->waitForText('menunggu verifikasi Hub')
                    ->assertSee('Listing berhasil dibuat');
        });

        $this->assertDatabaseHas('listings', [
            'title' => 'Sepeda Lipat Bekas',
            'review_status' => 'PENDING_REVIEW',
        ]);
        $this->assertSame(1, Listing::first()->images()->count());
    }
}
