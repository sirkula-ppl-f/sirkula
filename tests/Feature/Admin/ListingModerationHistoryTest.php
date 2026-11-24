<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingModerationAction;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PBI-06 / FR-021: halaman riwayat moderasi semua listing.
 */
class ListingModerationHistoryTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $query
     */
    private function riwayat(array $query = []): string
    {
        return route('admin.listings.moderations', $query);
    }

    /**
     * Baris riwayat pada waktu $waktuUtc.
     *
     * @param  array<string, mixed>  $atribut
     */
    private function catat(Listing $listing, string $waktuUtc, ListingModerationAction $aksi = ListingModerationAction::Deactivate, array $atribut = []): ListingModeration
    {
        return ListingModeration::factory()->forListing($listing)->byAdmin($this->admin)->create([
            'action' => $aksi,
            'created_at' => Carbon::parse($waktuUtc, 'UTC'),
            ...$atribut,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function ambil(array $query = []): array
    {
        $html = $this->actingAs($this->admin)->get($this->riwayat($query))->assertOk()->getContent();
        preg_match_all('/dusk="moderation-row-(\d+)"/', $html, $cocok);

        return array_map('intval', $cocok[1]);
    }

    private function jumlahQuery(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get($this->riwayat())->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    public function test_menampilkan_semua_riwayat_terbaru_di_atas_dengan_kolom_lengkap(): void
    {
        $kipas = Listing::factory()->create(['title' => 'Kipas Angin Berdiri']);
        $rak = Listing::factory()->create(['title' => 'Rak Sepatu Kayu']);

        $nonaktif = $this->catat($kipas, '2026-11-20 03:00:00', atribut: ['reason' => 'Foto listing memakai gambar dari internet.', 'request_id' => 'abcdef12-3456-4789-abcd-ef0123456789']);
        $pulihkan = $this->catat($kipas, '2026-11-21 04:30:00', ListingModerationAction::Restore, ['reason' => 'Penjual sudah mengganti foto listing.']);
        $lain = $this->catat($rak, '2026-11-22 10:15:00', atribut: ['reason' => 'Deskripsi listing menyesatkan pembeli.']);

        $this->assertSame([$lain->id, $pulihkan->id, $nonaktif->id], $this->ambil());

        $this->actingAs($this->admin)
            ->get($this->riwayat())
            ->assertOk()
            ->assertSee('Riwayat Moderasi Listing')
            ->assertSeeInOrder(['Waktu', 'Listing', 'Aksi', 'Alasan', 'Admin', 'ID permintaan'])
            ->assertSeeInOrder([
                '22 Nov 2026, 17.15 WIB', 'Rak Sepatu Kayu', 'ID '.$rak->id, 'Dinonaktifkan', 'Deskripsi listing menyesatkan pembeli.', 'Admin Moderator',
                '21 Nov 2026, 11.30 WIB', 'Kipas Angin Berdiri', 'Dipulihkan', 'Penjual sudah mengganti foto listing.',
                '20 Nov 2026, 10.00 WIB', 'Kipas Angin Berdiri', 'Dinonaktifkan', 'Foto listing memakai gambar dari internet.', 'abcdef12…',
            ])
            ->assertSee('title="abcdef12-3456-4789-abcd-ef0123456789"', false)
            ->assertSee('href="'.route('admin.listings.show', $kipas).'"', false)
            ->assertSee('href="'.route('admin.listings.show', $rak).'"', false)
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="Tabel riwayat moderasi listing"', false)
            ->assertSee('Riwayat moderasi listing, terbaru di atas, halaman 1 dari 1')
            ->assertSee('href="'.route('admin.listings.index').'"', false);
    }

    public function test_tidak_ada_aksi_ubah_atau_hapus_di_halaman_riwayat(): void
    {
        $this->catat(Listing::factory()->create(), '2026-11-20 03:00:00');

        $html = $this->actingAs($this->admin)->get($this->riwayat())->assertOk()->getContent();

        // Hanya isi halaman; form logout di sidebar memang memakai POST.
        $this->assertSame(1, preg_match('/<main\b.*<\/main>/s', $html, $main));
        $this->assertStringNotContainsString('method="POST"', $main[0]);
        $this->assertStringNotContainsString('name="_method"', $main[0]);
        $this->assertStringNotContainsString('data-confirm=', $main[0]);
    }

    public function test_alasan_panjang_dipotong_dan_lengkap_di_detail(): void
    {
        $listing = Listing::factory()->create();
        $alasan = 'Listing ini berulang kali dilaporkan pembeli karena kondisi barang tidak sesuai. '.str_repeat('Rincian laporan tambahan. ', 10).'AKHIR-ALASAN';
        $this->catat($listing, '2026-11-20 03:00:00', atribut: ['reason' => $alasan]);

        $this->actingAs($this->admin)
            ->get($this->riwayat())
            ->assertOk()
            ->assertSee(Str::limit($alasan, 100))
            ->assertDontSee('AKHIR-ALASAN');

        $this->actingAs($this->admin)
            ->get(route('admin.listings.show', $listing))
            ->assertOk()
            ->assertSee('AKHIR-ALASAN');
    }

    public function test_filter_aksi_kata_kunci_dan_rentang_tanggal_wib(): void
    {
        $kipas = Listing::factory()->create(['title' => 'Kipas Angin Berdiri']);
        $rak = Listing::factory()->create(['title' => 'Rak Sepatu Kayu']);

        $r1 = $this->catat($kipas, '2026-11-10 16:59:00');                                    // 23.59 WIB, 10 Nov
        $r2 = $this->catat($kipas, '2026-11-10 17:00:00', ListingModerationAction::Restore); // 00.00 WIB, 11 Nov
        $r3 = $this->catat($rak, '2026-11-11 16:59:59');                                      // 23.59 WIB, 11 Nov
        $r4 = $this->catat($rak, '2026-11-11 17:00:00', ListingModerationAction::Restore);   // 00.00 WIB, 12 Nov

        $this->assertSame([$r4->id, $r2->id], $this->ambil(['aksi' => 'RESTORE']));
        $this->assertSame([$r2->id, $r1->id], $this->ambil(['cari' => 'kipas']));
        $this->assertSame([$r4->id, $r3->id], $this->ambil(['cari' => (string) $rak->id]));
        $this->assertSame([$r3->id, $r2->id], $this->ambil(['dari' => '2026-11-11', 'sampai' => '2026-11-11']));
        $this->assertSame([$r4->id], $this->ambil(['dari' => '2026-11-12']));
        $this->assertSame([$r1->id], $this->ambil(['sampai' => '2026-11-10']));
        $this->assertSame([$r3->id], $this->ambil(['aksi' => 'DEACTIVATE', 'dari' => '2026-11-11']));

        $this->actingAs($this->admin)
            ->get($this->riwayat(['aksi' => 'DEACTIVATE', 'cari' => 'Lemari']))
            ->assertOk()
            ->assertSee('Tidak ada riwayat moderasi yang cocok dengan filter.')
            ->assertSee('dusk="moderation-empty-reset"', false)
            ->assertSee('<option value="DEACTIVATE" selected', false)
            ->assertSee('value="Lemari"', false);
    }

    public function test_kata_kunci_dengan_karakter_khusus_dicari_sebagai_teks(): void
    {
        $diskon = Listing::factory()->create(['title' => 'Diskon 50% Kursi']);
        $biasa = Listing::factory()->create(['title' => 'Kursi 50 cm']);
        $r1 = $this->catat($diskon, '2026-11-20 03:00:00');
        $this->catat($biasa, '2026-11-20 04:00:00');

        $this->assertSame([$r1->id], $this->ambil(['cari' => '50%']));
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        $r1 = $this->catat(Listing::factory()->create(), '2026-11-20 03:00:00');

        $this->assertSame([$r1->id], $this->ambil(['aksi' => 'HAPUS', 'dari' => '2026-13-45', 'sampai' => 'kemarin', 'cari' => ['a']]));
        $this->actingAs($this->admin)
            ->get($this->riwayat(['aksi' => 'HAPUS', 'dari' => '2026-13-45']))
            ->assertDontSee('dusk="moderation-filter-reset"', false);
    }

    public function test_listing_draf_tanpa_tautan_detail(): void
    {
        $listing = Listing::factory()->create(['title' => 'Meja Lipat Draf']);
        $moderation = $this->catat($listing, '2026-11-20 03:00:00');
        // Fixture: listing kembali menjadi draf setelah dimoderasi (misalnya alur revisi PBI-07 nanti).
        DB::table('listings')->where('id', $listing->id)->update(['review_status' => Listing::REVIEW_DRAFT]);

        $this->actingAs($this->admin)
            ->get($this->riwayat())
            ->assertOk()
            ->assertSee('Meja Lipat Draf')
            ->assertDontSee('dusk="moderation-listing-link-'.$moderation->id.'"', false)
            ->assertDontSee('href="'.route('admin.listings.show', $listing).'"', false);
    }

    public function test_paginasi_15_per_halaman_dan_halaman_kosong_dialihkan(): void
    {
        $listing = Listing::factory()->create();

        for ($i = 1; $i <= 17; $i++) {
            $this->catat($listing, sprintf('2026-11-%02d 03:00:00', $i));
        }

        $filter = ['aksi' => 'DEACTIVATE'];

        $this->assertCount(15, $this->ambil($filter));
        $this->assertCount(2, $this->ambil([...$filter, 'page' => 2]));
        $this->actingAs($this->admin)
            ->get($this->riwayat($filter))
            ->assertSee(e($this->riwayat([...$filter, 'page' => 2])), false);

        $this->actingAs($this->admin)
            ->get($this->riwayat([...$filter, 'page' => 9]))
            ->assertRedirect($this->riwayat([...$filter, 'page' => 2]));
    }

    public function test_keadaan_kosong_tanpa_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->riwayat())
            ->assertOk()
            ->assertSee('Belum ada riwayat moderasi listing.')
            ->assertDontSee('dusk="moderation-empty-reset"', false);
    }

    public function test_jumlah_query_tetap_untuk_2_dan_20_riwayat(): void
    {
        foreach (range(1, 2) as $i) {
            ListingModeration::factory()->create();
        }

        $this->jumlahQuery();
        $denganDua = $this->jumlahQuery();

        // 18 riwayat lagi dengan listing dan admin yang berbeda-beda.
        ListingModeration::factory()->count(18)->create();

        $this->assertSame($denganDua, $this->jumlahQuery(), 'Jumlah query halaman riwayat harus sama untuk 2 dan 20 baris.');
    }
}
