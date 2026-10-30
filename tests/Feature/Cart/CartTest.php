<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-11 / FR-014. Memakai Listing stub; sesuaikan factory bila model asli (PBI-07/08) sudah masuk.
 */
class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('cart.index'))->assertRedirect(route('login'));
        $this->post(route('cart.store'), [])->assertRedirect(route('login'));
    }

    public function test_menambah_item_menyimpan_harga_saat_itu(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['price' => 50000]);

        $this->actingAs($user)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'price_seen' => 50000,
        ]);
    }

    public function test_menambah_listing_yang_sama_menjumlahkan_kuantitas(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->stok(5)->create();

        $this->actingAs($user)->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 2]);
        $this->actingAs($user)->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 1]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(3, CartItem::first()->quantity);
    }

    public function test_tidak_bisa_membeli_listing_sendiri(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 1])
            ->assertSessionHasErrors('listing_id');

        $this->assertSame(0, CartItem::count());
    }

    public function test_listing_belum_disetujui_atau_stok_habis_ditolak(): void
    {
        $user = User::factory()->create();

        $belum = Listing::factory()->belumDisetujui()->create();
        $habis = Listing::factory()->stok(0)->create();

        foreach ([$belum, $habis] as $listing) {
            $this->actingAs($user)
                ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 1])
                ->assertSessionHasErrors('listing_id');
        }

        $this->assertSame(0, CartItem::count());
    }

    public function test_listing_penjual_ditangguhkan_ditolak(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => User::factory()->suspended()]);

        $this->actingAs($user)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 1])
            ->assertSessionHasErrors('listing_id');
    }

    public function test_kuantitas_melebihi_stok_ditolak(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->stok(2)->create();

        $this->actingAs($user)
            ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');
    }

    public function test_kuantitas_harus_bilangan_bulat_minimal_satu(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create();

        foreach ([0, -1, 1.5, 'abc'] as $nilai) {
            $this->actingAs($user)
                ->post(route('cart.store'), ['listing_id' => $listing->id, 'quantity' => $nilai])
                ->assertSessionHasErrors('quantity');
        }
    }

    public function test_mengubah_dan_menghapus_item(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->stok(5)->create();
        $item = CartItem::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => $listing->price]);

        $this->actingAs($user)->patch(route('cart.update', $item->id), ['quantity' => 4])->assertRedirect(route('cart.index'));
        $this->assertSame(4, $item->fresh()->quantity);

        $this->actingAs($user)->delete(route('cart.destroy', $item->id))->assertRedirect(route('cart.index'));
        $this->assertModelMissing($item);
    }

    public function test_mengubah_kuantitas_melebihi_stok_ditolak_dan_error_per_item(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->stok(2)->create();
        $item = CartItem::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => $listing->price]);

        $this->actingAs($user)
            ->patch(route('cart.update', $item->id), ['quantity' => 9])
            ->assertSessionHasErrors('quantity', null, 'cart-item-'.$item->id);

        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_item_milik_orang_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();
        $listing = Listing::factory()->create();
        $item = CartItem::create(['user_id' => $pemilik->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => $listing->price]);

        $this->actingAs($lain)->patch(route('cart.update', $item->id), ['quantity' => 2])->assertNotFound();
        $this->actingAs($lain)->delete(route('cart.destroy', $item->id))->assertNotFound();

        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_keranjang_dikelompokkan_per_penjual_dengan_subtotal(): void
    {
        $user = User::factory()->create();
        $penjualA = User::factory()->create(['name' => 'Penjual A']);
        $penjualB = User::factory()->create(['name' => 'Penjual B']);

        $a1 = Listing::factory()->create(['user_id' => $penjualA->id, 'price' => 10000]);
        $a2 = Listing::factory()->create(['user_id' => $penjualA->id, 'price' => 5000]);
        $b1 = Listing::factory()->create(['user_id' => $penjualB->id, 'price' => 7000]);

        foreach ([[$a1, 2], [$a2, 1], [$b1, 3]] as [$listing, $qty]) {
            CartItem::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'quantity' => $qty, 'price_seen' => $listing->price]);
        }

        $groups = app(CartService::class)->groupedBySeller($user);

        $this->assertCount(2, $groups);
        $this->assertSame(25000, $groups[0]['subtotal']);
        $this->assertSame(21000, $groups[1]['subtotal']);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Penjual A')
            ->assertSee('Penjual B');
    }

    public function test_item_ditandai_bila_harga_atau_stok_berubah(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->stok(5)->create(['price' => 60000]);
        CartItem::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'quantity' => 4, 'price_seen' => 60000]);

        $listing->update(['price' => 65000, 'stock' => 2]);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Harga berubah: Rp60.000 menjadi Rp65.000')
            ->assertSee('Stok tersisa 2');
    }

    public function test_keranjang_kosong(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Keranjang masih kosong.');
    }

    public function test_keranjang_hanya_menampilkan_milik_sendiri(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $listing = Listing::factory()->create(['title' => 'Barang Rahasia']);
        CartItem::create(['user_id' => $lain->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => $listing->price]);

        $this->actingAs($user)->get(route('cart.index'))->assertDontSee('Barang Rahasia');
    }
}
