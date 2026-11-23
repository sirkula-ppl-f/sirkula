<?php

namespace Tests\Feature\ListingModeration;

use App\Models\CartItem;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use App\Services\ListingModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * PBI-06: listing yang dinonaktifkan admin hilang dari katalog, detail, dan keranjang lewat satu titik
 * aturan (Listing::scopePurchasable() dan isPurchasable()).
 */
class ListingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ListingModerationService $moderasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->moderasi = app(ListingModerationService::class);
    }

    private function nonaktifkan(Listing $listing): void
    {
        $this->moderasi->deactivate($listing, $this->admin, 'Foto listing tidak sesuai dengan barang.', 'req-uji-visibilitas');
    }

    private function hub(string $status): Hub
    {
        $region = Region::query()->firstOrCreate(['code' => 'PC'], ['name' => 'Provinsi Contoh']);

        return Hub::create([
            'name' => 'Hub '.$status,
            'type' => 'KOMUNITAS',
            'region_id' => $region->id,
            'public_address' => 'Jl. Contoh No. 1',
            'status' => $status,
        ]);
    }

    public function test_listing_dinonaktifkan_tidak_termasuk_purchasable(): void
    {
        $tampil = Listing::factory()->create();
        $disembunyikan = Listing::factory()->create();
        $fixture = Listing::factory()->dinonaktifkanAdmin()->create();

        $this->nonaktifkan($disembunyikan);

        $this->assertSame([$tampil->id], Listing::purchasable()->pluck('id')->all());
        $this->assertTrue($tampil->fresh()->isPurchasable());
        $this->assertFalse($disembunyikan->fresh()->isPurchasable());
        $this->assertFalse($disembunyikan->isPurchasable(), 'Instance pemanggil ikut diperbarui service.');
        $this->assertFalse($fixture->isPurchasable());
    }

    public function test_katalog_tidak_menampilkan_listing_dinonaktifkan_dan_detailnya_404(): void
    {
        $tampil = Listing::factory()->create(['title' => 'Rak Buku Kayu Tampil']);
        $disembunyikan = Listing::factory()->create(['title' => 'Kursi Lipat Disembunyikan']);

        $this->get(route('catalog.show', $disembunyikan->id))->assertOk();

        $this->nonaktifkan($disembunyikan);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Rak Buku Kayu Tampil')
            ->assertDontSee('Kursi Lipat Disembunyikan');

        $this->get(route('catalog.index', ['search' => 'Kursi Lipat']))
            ->assertOk()
            ->assertDontSee('Kursi Lipat Disembunyikan');

        $this->get(route('catalog.show', $disembunyikan->id))->assertNotFound();
        $this->get(route('catalog.show', $tampil->id))->assertOk();

        // Warga yang login juga mendapat 404.
        $this->actingAs(User::factory()->create())->get(route('catalog.show', $disembunyikan->id))->assertNotFound();
    }

    public function test_item_keranjang_atas_listing_dinonaktifkan_ditandai_tidak_tersedia_dan_tidak_bisa_ditambah(): void
    {
        $pembeli = User::factory()->create();
        $listing = Listing::factory()->stok(5)->create();
        $item = CartItem::create(['user_id' => $pembeli->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => $listing->price]);

        $this->nonaktifkan($listing);

        // Item tidak dihapus oleh moderasi, hanya ditandai tidak tersedia.
        $this->assertModelExists($item);
        $this->assertTrue($item->fresh()->unavailable());

        $this->actingAs($pembeli)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('dusk="flag-unavailable"', false)
            ->assertSee('Barang ini sudah tidak tersedia. Hapus dari keranjang.');

        $this->actingAs($pembeli)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 1])
            ->assertSessionHasErrors('listing_id');

        $this->actingAs($pembeli)
            ->patch(route('cart.update', $item->id), ['quantity' => 2])
            ->assertSessionHasErrors('quantity', null, 'cart-item-'.$item->id);

        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_alasan_tidak_tayang_kosong_jika_dan_hanya_jika_purchasable(): void
    {
        $hubAktif = $this->hub('ACTIVE');
        $hubArsip = $this->hub('ARCHIVED');
        $penjualDitangguhkan = User::factory()->suspended()->create();

        // [atribut listing, alasan yang diharapkan (urut sama dengan syarat purchasable)]
        $kasus = [
            'tampil' => [[], []],
            'tampil dengan hub aktif' => [['hub_id' => $hubAktif->id], []],
            'dinonaktifkan admin' => [['moderation_status' => 'HIDDEN'], ['moderated']],
            'menunggu verifikasi hub' => [['review_status' => 'PENDING_REVIEW'], ['review_pending']],
            'ditolak hub' => [['review_status' => 'REJECTED'], ['review_not_approved']],
            'nilai PENDING dari factory' => [['review_status' => 'PENDING'], ['review_not_approved']],
            'tidak dipublikasikan' => [['publish_status' => 'INACTIVE'], ['not_published']],
            'stok habis' => [['stock' => 0], ['out_of_stock']],
            'penjual ditangguhkan' => [['user_id' => $penjualDitangguhkan->id], ['seller_inactive']],
            'hub diarsipkan' => [['hub_id' => $hubArsip->id], ['hub_inactive']],
            'gabungan' => [
                ['moderation_status' => 'HIDDEN', 'review_status' => 'PENDING_REVIEW', 'publish_status' => 'INACTIVE', 'stock' => 0, 'user_id' => $penjualDitangguhkan->id, 'hub_id' => $hubArsip->id],
                ['moderated', 'review_pending', 'not_published', 'out_of_stock', 'seller_inactive', 'hub_inactive'],
            ],
        ];

        foreach ($kasus as $nama => [$atribut, $harapan]) {
            $id = Listing::factory()->create($atribut)->id;
            $listing = Listing::query()->with(['seller', 'hub'])->findOrFail($id);
            $alasan = $this->moderasi->visibilityBlockers($listing);

            $this->assertSame(
                array_map(fn (string $key) => 'listing-moderation.blockers.'.$key, $harapan),
                $alasan,
                "Kasus: {$nama}"
            );
            $this->assertSame($alasan === [], $listing->isPurchasable(), "Kasus: {$nama}");
            $this->assertSame($listing->isPurchasable(), Listing::purchasable()->whereKey($id)->exists(), "Kasus: {$nama} (scope dan method harus sama)");

            foreach ($alasan as $key) {
                $this->assertTrue(Lang::has($key), "Key lang {$key} belum ada.");
            }
        }
    }
}
