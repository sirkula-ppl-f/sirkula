<?php

namespace Tests\Browser;

use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * PBI-11 / FR-014. Tombol "Tambah ke Keranjang" ada di halaman detail (PBI-10), jadi tes ini
 * mengisi keranjang lewat database dan menguji halaman keranjang. Tambahkan langkah klik
 * "Tambah ke Keranjang" setelah PBI-10 masuk develop.
 */
class CartFlowTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function isiKeranjang(User $pembeli, Listing $listing, int $jumlah): CartItem
    {
        return CartItem::create([
            'user_id' => $pembeli->id,
            'listing_id' => $listing->id,
            'quantity' => $jumlah,
            'price_seen' => $listing->price,
        ]);
    }

    public function test_keranjang_dikelompokkan_per_penjual_dengan_subtotal(): void
    {
        $pembeli = User::factory()->create();
        $penjualA = User::factory()->create(['name' => 'Bank Sampah RW 05 Dago']);
        $penjualB = User::factory()->create(['name' => 'Kriya Lembang']);

        $this->isiKeranjang($pembeli, Listing::factory()->create(['user_id' => $penjualA->id, 'title' => 'Botol PET Terpilah', 'price' => 112500]), 1);
        $this->isiKeranjang($pembeli, Listing::factory()->create(['user_id' => $penjualA->id, 'title' => 'Kardus Bekas', 'price' => 65000]), 2);
        $this->isiKeranjang($pembeli, Listing::factory()->create(['user_id' => $penjualB->id, 'title' => 'Dompet dari Ban Dalam', 'price' => 95000]), 1);

        $this->browse(function (Browser $browser) use ($pembeli) {
            $browser->loginAs($pembeli)
                ->visit('/keranjang')
                ->assertPresent('@cart-group')
                ->assertSee('Penjual: Bank Sampah RW 05 Dago')
                ->assertSee('Penjual: Kriya Lembang')
                ->assertSeeIn('@subtotal', 'Rp242.500')
                ->assertSee('Rp95.000')
                ->assertSee('Keranjang tidak mengunci stok');
        });
    }

    public function test_mengubah_jumlah_memperbarui_subtotal(): void
    {
        $pembeli = User::factory()->create();
        $listing = Listing::factory()->stok(10)->create(['price' => 10000]);
        $item = $this->isiKeranjang($pembeli, $listing, 1);

        $this->browse(function (Browser $browser) use ($pembeli, $item) {
            $browser->loginAs($pembeli)
                ->visit('/keranjang')
                ->assertSeeIn('@subtotal', 'Rp10.000')
                ->type('@qty-'.$item->id, '3')
                ->click('@update-'.$item->id)
                ->waitForText('Jumlah barang diperbarui.')
                ->assertSeeIn('@subtotal', 'Rp30.000')
                ->assertInputValue('@qty-'.$item->id, '3');
        });
    }

    public function test_jumlah_melebihi_stok_menampilkan_pesan_kesalahan(): void
    {
        $pembeli = User::factory()->create();
        $listing = Listing::factory()->stok(2)->create(['price' => 10000]);
        $item = $this->isiKeranjang($pembeli, $listing, 1);

        $this->browse(function (Browser $browser) use ($pembeli, $item) {
            $browser->loginAs($pembeli)
                ->visit('/keranjang')
                ->type('@qty-'.$item->id, '9')
                ->click('@update-'.$item->id)
                ->waitFor('@error-'.$item->id)
                ->assertSeeIn('@error-'.$item->id, 'Stok tersisa 2')
                ->assertSeeIn('@subtotal', 'Rp10.000');
        });
    }

    public function test_menghapus_item_menampilkan_keranjang_kosong(): void
    {
        $pembeli = User::factory()->create();
        $item = $this->isiKeranjang($pembeli, Listing::factory()->create(), 1);

        $this->browse(function (Browser $browser) use ($pembeli, $item) {
            $browser->loginAs($pembeli)
                ->visit('/keranjang')
                ->assertPresent('@cart-item')
                ->click('@remove-'.$item->id)
                ->waitForText('Barang dihapus dari keranjang.')
                ->assertPresent('@cart-empty')
                ->assertMissing('@cart-item');
        });
    }

    public function test_perubahan_harga_dan_stok_ditandai(): void
    {
        $pembeli = User::factory()->create();
        $listing = Listing::factory()->stok(5)->create(['price' => 60000]);
        $this->isiKeranjang($pembeli, $listing, 4);

        $listing->update(['price' => 65000, 'stock' => 2]);

        $this->browse(function (Browser $browser) use ($pembeli) {
            $browser->loginAs($pembeli)
                ->visit('/keranjang')
                ->assertSeeIn('@flag-price', 'Harga berubah: Rp60.000 menjadi Rp65.000')
                ->assertSeeIn('@flag-stock', 'Stok tersisa 2');
        });
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/keranjang')
                ->assertPathIs('/login');
        });
    }
}
