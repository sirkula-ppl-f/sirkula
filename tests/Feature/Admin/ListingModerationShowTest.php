<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingModerationAction;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingModeration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PBI-06: halaman detail listing admin (informasi, alasan tidak tayang, jumlah pesanan, form aksi, riwayat).
 */
class ListingModerationShowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 12.00 WIB, 24 November 2026.
        $this->travelTo(Carbon::parse('2026-11-24 05:00:00', 'UTC'));
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Moderator']);
    }

    private function detail(Listing $listing): string
    {
        return $this->actingAs($this->admin)->get(route('admin.listings.show', $listing))->assertOk()->getContent();
    }

    private function pesanan(Listing $listing, int $jumlahItem = 1): Order
    {
        $order = Order::factory()->create(['seller_id' => $listing->user_id]);

        OrderItem::factory()->count($jumlahItem)->create(['order_id' => $order->id, 'listing_id' => $listing->id]);

        return $order;
    }

    public function test_menampilkan_informasi_status_dan_jumlah_pesanan(): void
    {
        $provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        $hub = Hub::create(['name' => 'Bank Sampah Alfa', 'type' => 'BANK_SAMPAH', 'region_id' => $provinsi->id, 'public_address' => 'Jl. Contoh No. 1', 'status' => 'ACTIVE']);
        $penjual = User::factory()->create(['name' => 'Penjual Dua', 'email' => 'penjual.dua@contoh.test']);
        $listing = Listing::factory()->create([
            'title' => 'Kipas Angin Berdiri',
            'price' => 120000,
            'stock' => 4,
            'sale_unit' => 'ITEM',
            'item_condition' => 'Baik',
            'category_id' => Category::factory()->create(['name' => 'Elektronik Rumah'])->id,
            'user_id' => $penjual->id,
            'hub_id' => $hub->id,
            'region_id' => $provinsi->id,
        ]);

        // Dua pesanan atas listing ini (salah satunya memuat dua item) dan satu pesanan listing lain.
        $this->pesanan($listing);
        $this->pesanan($listing, 2);
        $this->pesanan(Listing::factory()->create());

        $html = $this->actingAs($this->admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSee('Detail Listing')
            ->assertSeeInOrder(['Kipas Angin Berdiri', 'ID '.$listing->id])
            ->assertSeeInOrder(['Barang Bekas', 'Elektronik Rumah', 'Baik', 'Penjual Dua', 'penjual.dua@contoh.test', 'Bank Sampah Alfa', 'Provinsi Alfa', 'Rp 120.000 per barang', '4', '24 Nov 2026, 12.00 WIB'])
            ->assertSeeInOrder(['Disetujui hub', 'Dipublikasikan', 'Diizinkan tampil', 'Ya'])
            ->assertSee('Listing ini tercatat di 2 pesanan.')
            ->assertSee('Moderasi tidak mengubah pesanan lama, dan pembayaran yang sedang berjalan tetap diproses.')
            ->assertDontSee('dusk="listing-blockers"', false)
            ->assertSee('href="'.route('admin.listings.index').'"', false)
            ->getContent();

        // Form nonaktifkan dengan dialog konfirmasi; form pulihkan tidak ada.
        $this->assertStringContainsString('action="'.route('admin.listings.deactivate', $listing).'"', $html);
        $this->assertStringContainsString('dusk="listing-deactivate-form"', $html);
        $this->assertStringContainsString('data-confirm="Listing “Kipas Angin Berdiri” akan langsung hilang dari katalog. Pesanan lama tidak berubah."', $html);
        $this->assertStringContainsString('data-confirm-variant="danger"', $html);
        $this->assertStringContainsString('data-submit-once', $html);
        $this->assertStringContainsString('name="_method" value="PATCH"', $html);
        $this->assertStringContainsString('data-confirm-dialog', $html);
        $this->assertStringNotContainsString('dusk="listing-restore-form"', $html);
        $this->assertStringNotContainsString(route('admin.listings.restore', $listing), $html);
    }

    public function test_textarea_alasan_berlabel_dan_aksesibel(): void
    {
        $html = $this->detail(Listing::factory()->create());

        $this->assertStringContainsString('<label for="reason"', $html);
        $this->assertMatchesRegularExpression('/<textarea\s[^>]*id="reason"[^>]*>/s', $html);
        $this->assertSame(1, preg_match('/<textarea\s[^>]*id="reason"[^>]*>/s', $html, $textarea));
        $this->assertStringContainsString('aria-required="true"', $textarea[0]);
        $this->assertStringContainsString('maxlength="500"', $textarea[0]);
        $this->assertStringContainsString('aria-describedby="reason-hint"', $textarea[0]);
        $this->assertStringNotContainsString('aria-invalid', $textarea[0]);
        $this->assertStringNotContainsString('autofocus', $textarea[0]);
        $this->assertStringContainsString('10–500 karakter. Alasan hanya terlihat oleh admin', $html);
    }

    public function test_listing_dinonaktifkan_menampilkan_form_pulihkan_dan_alasan_tidak_tayang(): void
    {
        $listing = Listing::factory()->dinonaktifkanAdmin()->stok(0)->create([
            'title' => 'Sepeda Lipat',
            'review_status' => Listing::REVIEW_PENDING,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSeeInOrder(['Menunggu verifikasi hub', 'Dipublikasikan', 'Dinonaktifkan admin', 'Tidak'])
            ->assertSeeInOrder(['Alasan listing tidak tampil di katalog:', 'Dinonaktifkan admin.', 'Menunggu verifikasi hub.', 'Stok habis.'])
            ->assertSee('Listing ini tercatat di 0 pesanan.')
            ->getContent();

        $this->assertStringContainsString('action="'.route('admin.listings.restore', $listing).'"', $html);
        $this->assertStringContainsString('dusk="listing-restore-form"', $html);
        $this->assertStringContainsString('tetap harus lolos verifikasi hub', $html);
        $this->assertStringNotContainsString('dusk="listing-deactivate-form"', $html);
        $this->assertStringNotContainsString('data-confirm-variant="danger"', $html);
    }

    public function test_riwayat_listing_terbaru_di_atas_dengan_id_permintaan_dipendekkan(): void
    {
        $listing = Listing::factory()->create();
        $lama = ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->create([
            'reason' => 'Foto listing memakai gambar dari internet.',
            'request_id' => 'abcdef12-3456-4789-abcd-ef0123456789',
            'created_at' => Carbon::parse('2026-11-20 03:00:00', 'UTC'),
        ]);
        $baru = ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->restore()->create([
            'reason' => 'Penjual sudah mengganti foto dengan foto asli.',
            'request_id' => 'req-pulihkan-0001',
            'review_status_snapshot' => Listing::REVIEW_PENDING,
            'created_at' => Carbon::parse('2026-11-21 04:30:00', 'UTC'),
        ]);
        ListingModeration::factory()->create(['reason' => 'Riwayat listing lain yang tidak boleh tampil.']);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSeeInOrder(['Waktu', 'Aksi', 'Alasan', 'Admin', 'ID permintaan', 'Status verifikasi saat itu'])
            ->assertSeeInOrder([
                '21 Nov 2026, 11.30 WIB', 'Dipulihkan', 'Penjual sudah mengganti foto dengan foto asli.', 'Admin Moderator', 'req-puli…', 'Menunggu verifikasi hub',
                '20 Nov 2026, 10.00 WIB', 'Dinonaktifkan', 'Foto listing memakai gambar dari internet.', 'Admin Moderator', 'abcdef12…', 'Disetujui hub',
            ])
            ->assertSee('title="abcdef12-3456-4789-abcd-ef0123456789"', false)
            ->assertSee('title="req-pulihkan-0001"', false)
            ->assertDontSee('Riwayat listing lain yang tidak boleh tampil.')
            ->assertDontSee('dusk="listing-history-empty"', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'dusk="listing-history-row-'.$lama->id.'"'),
            strpos($html, 'dusk="listing-history-row-'.$baru->id.'"')
        );
    }

    public function test_tanpa_riwayat_dan_tanpa_foto_menampilkan_keadaan_kosong(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.listings.show', Listing::factory()->create()))
            ->assertOk()
            ->assertSee('Belum ada riwayat moderasi untuk listing ini.')
            ->assertSee('Belum ada foto.');
    }

    public function test_foto_utama_ditampilkan_bila_ada(): void
    {
        $listing = Listing::factory()->create(['title' => 'Meja Belajar']);
        ListingImage::create(['listing_id' => $listing->id, 'path_foto' => 'listings/kedua.jpg', 'is_primary' => false]);
        ListingImage::create(['listing_id' => $listing->id, 'path_foto' => 'listings/utama.jpg', 'is_primary' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSee('src="'.Storage::url('listings/utama.jpg').'"', false)
            ->assertSee('alt="Foto utama Meja Belajar"', false)
            ->assertDontSee(Storage::url('listings/kedua.jpg'), false);
    }

    public function test_listing_draf_id_tidak_ada_dan_bukan_angka_404(): void
    {
        $draf = Listing::factory()->create(['review_status' => Listing::REVIEW_DRAFT]);

        $this->actingAs($this->admin)->get(route('admin.listings.show', $draf))->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/listing/999999')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/listing/abc')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/listing/99999999999999999999')->assertNotFound();
    }

    public function test_aksi_riwayat_memakai_label_enum(): void
    {
        $listing = Listing::factory()->create();
        ListingModeration::factory()->forListing($listing)->create(['action' => ListingModerationAction::Deactivate]);

        $this->assertStringContainsString('Dinonaktifkan', $this->detail($listing));
    }
}
