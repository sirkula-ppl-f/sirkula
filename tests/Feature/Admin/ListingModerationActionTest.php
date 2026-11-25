<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\User;
use App\Services\ListingModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PBI-06 / FR-020, FR-021: nonaktifkan dan pulihkan listing lewat halaman admin.
 */
class ListingModerationActionTest extends TestCase
{
    use RefreshDatabase;

    private const ALASAN = 'Foto listing tidak sesuai dengan barang yang dijual.';

    private User $admin;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->listing = Listing::factory()->create(['title' => 'Lemari Pakaian Jati']);
    }

    private function detail(?Listing $listing = null): string
    {
        return route('admin.listings.show', $listing ?? $this->listing);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function kirim(string $aksi, array $data, ?Listing $listing = null)
    {
        return $this->actingAs($this->admin)
            ->from($this->detail($listing))
            ->patch(route('admin.listings.'.$aksi, $listing ?? $this->listing), $data);
    }

    private function statusDb(?Listing $listing = null): string
    {
        return DB::table('listings')->where('id', ($listing ?? $this->listing)->id)->value('moderation_status');
    }

    private function nonaktifkanLewatService(Listing $listing): void
    {
        app(ListingModerationService::class)->deactivate($listing, $this->admin, self::ALASAN, 'req-persiapan-01');
    }

    public function test_menonaktifkan_lewat_form_tercatat_dengan_id_permintaan_ip_dan_user_agent(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.8'])
            ->withHeaders(['User-Agent' => 'Peramban Admin/2.0', 'X-Request-Id' => 'req-admin-12345'])
            ->kirim('deactivate', ['reason' => '  '.self::ALASAN.'  '])
            ->assertRedirect($this->detail())
            ->assertSessionHas('status', 'Listing “Lemari Pakaian Jati” dinonaktifkan dan tidak lagi tampil di katalog.');

        $this->assertSame('HIDDEN', $this->statusDb());

        $moderation = ListingModeration::sole();
        $this->assertSame(ListingModerationAction::Deactivate, $moderation->action);
        $this->assertSame(self::ALASAN, $moderation->reason);
        $this->assertSame('req-admin-12345', $moderation->request_id);
        $this->assertSame($this->admin->id, $moderation->admin_id);

        $audit = AuditLog::sole();
        $this->assertSame('LISTING_DEACTIVATED', $audit->action);
        $this->assertSame('SUCCESS', $audit->result);
        $this->assertSame('10.0.0.8', $audit->ip_address);
        $this->assertSame('Peramban Admin/2.0', $audit->user_agent);

        // Halaman detail sesudahnya: flash sukses, status baru, form pulihkan, dan baris riwayat.
        $this->actingAs($this->admin)
            ->get($this->detail())
            ->assertOk()
            ->assertSee('dusk="listing-restore-form"', false)
            ->assertSee('dusk="listing-history-row-'.$moderation->id.'"', false)
            ->assertSee('Dinonaktifkan admin.');
    }

    public function test_tanpa_header_id_permintaan_dibuat_uuid(): void
    {
        $this->withHeaders(['X-Request-Id' => 'tidak valid!'])
            ->kirim('deactivate', ['reason' => self::ALASAN])
            ->assertRedirect($this->detail());

        $this->assertTrue(Str::isUuid(ListingModeration::sole()->request_id));
    }

    public function test_memulihkan_listing_yang_disetujui_tanpa_catatan_tambahan(): void
    {
        $this->nonaktifkanLewatService($this->listing);

        $this->kirim('restore', ['reason' => 'Penjual sudah memperbaiki foto listing.'])
            ->assertRedirect($this->detail())
            ->assertSessionHas('status', 'Listing “Lemari Pakaian Jati” dipulihkan.')
            ->assertSessionMissing('visibility_note');

        $this->assertSame('VISIBLE', $this->statusDb());
        $this->assertTrue($this->listing->fresh()->isPurchasable());
        $this->get(route('catalog.show', $this->listing->id))->assertOk();
        $this->assertSame(['LISTING_DEACTIVATED', 'LISTING_RESTORED'], AuditLog::query()->orderBy('id')->pluck('action')->all());
    }

    public function test_memulihkan_listing_yang_menunggu_verifikasi_hub_memberi_catatan_belum_tayang(): void
    {
        $this->nonaktifkanLewatService($this->listing);
        DB::table('listings')->where('id', $this->listing->id)->update(['review_status' => Listing::REVIEW_PENDING]);

        $this->kirim('restore', ['reason' => 'Penjual sudah merevisi deskripsi listing.'])
            ->assertRedirect($this->detail())
            ->assertSessionHas('visibility_note', 'Listing belum tampil di katalog karena: Menunggu verifikasi hub.');

        $this->assertSame('VISIBLE', $this->statusDb());
        $this->assertSame(Listing::REVIEW_PENDING, $this->listing->fresh()->review_status);
        $this->get(route('catalog.show', $this->listing->id))->assertNotFound();

        // Nonaktifkan lagi, lalu pulihkan saat listing ditolak hub: catatan tampil di halaman detail setelah redirect.
        $this->kirim('deactivate', ['reason' => self::ALASAN])->assertSessionHasNoErrors();
        DB::table('listings')->where('id', $this->listing->id)->update(['review_status' => 'REJECTED']);
        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($this->detail())
            ->patch(route('admin.listings.restore', $this->listing), ['reason' => 'Pemulihan kedua untuk diuji tampilannya.'])
            ->assertOk()
            ->assertSee('dusk="listing-visibility-note"', false)
            ->assertSee('Listing belum tampil di katalog karena: Belum atau tidak disetujui hub.');
    }

    public function test_alasan_tidak_valid_ditolak_dan_nilai_lama_terjaga(): void
    {
        // Pasangan [alasan, pesan]. Bukan key array, karena PHP mengubah key '123456789' menjadi integer.
        $kasus = [
            ['', 'Alasan moderasi wajib diisi.'],
            ['      ', 'Alasan moderasi wajib diisi.'],
            ['123456789', 'Alasan moderasi minimal 10 karakter.'],
            ['  12345678  ', 'Alasan moderasi minimal 10 karakter.'],
            [str_repeat('a', 501), 'Alasan moderasi maksimal 500 karakter.'],
        ];

        foreach ($kasus as [$alasan, $pesan]) {
            $this->kirim('deactivate', ['reason' => $alasan])
                ->assertRedirect($this->detail())
                ->assertSessionHasErrors(['reason' => $pesan]);
        }

        $this->kirim('deactivate', [])->assertSessionHasErrors(['reason' => 'Alasan moderasi wajib diisi.']);
        $this->kirim('deactivate', ['reason' => ['bukan', 'teks']])->assertSessionHasErrors(['reason' => 'Alasan moderasi harus berupa teks.']);

        $this->assertSame('VISIBLE', $this->statusDb());
        $this->assertDatabaseCount('listing_moderations', 0);
        $this->assertDatabaseCount('audit_logs', 0);

        // Halaman detail menampilkan error di textarea, nilai lama, dan fokus.
        $html = $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($this->detail())
            ->patch(route('admin.listings.deactivate', $this->listing), ['reason' => '123456789'])
            ->assertOk()
            ->assertSee('Alasan moderasi minimal 10 karakter.')
            ->getContent();

        $this->assertSame(1, preg_match('/<textarea\s[^>]*id="reason"[^>]*>([^<]*)<\/textarea>/s', $html, $textarea));
        $this->assertStringContainsString('aria-invalid="true"', $textarea[0]);
        $this->assertStringContainsString('aria-describedby="reason-hint reason-error"', $textarea[0]);
        $this->assertStringContainsString('autofocus', $textarea[0]);
        $this->assertSame('123456789', $textarea[1]);
    }

    public function test_batas_panjang_alasan_10_dan_500_karakter_diterima(): void
    {
        $this->kirim('deactivate', ['reason' => 'Sepuluh k.'])->assertSessionHasNoErrors();
        $this->kirim('restore', ['reason' => str_repeat('b', 500)])->assertSessionHasNoErrors();

        $this->assertSame(['Sepuluh k.', str_repeat('b', 500)], ListingModeration::query()->orderBy('id')->pluck('reason')->all());
    }

    public function test_aksi_ganda_menampilkan_flash_error_tanpa_500(): void
    {
        $this->kirim('deactivate', ['reason' => self::ALASAN])->assertRedirect($this->detail());

        // Form lama di tab lain dikirim lagi.
        $this->kirim('deactivate', ['reason' => self::ALASAN])
            ->assertRedirect($this->detail())
            ->assertSessionHas('error', __('listing-moderation.errors.already_hidden'));

        $tampil = Listing::factory()->create();
        $this->kirim('restore', ['reason' => self::ALASAN], $tampil)
            ->assertRedirect($this->detail($tampil))
            ->assertSessionHas('error', __('listing-moderation.errors.already_visible'));

        $this->assertDatabaseCount('listing_moderations', 1);
        $this->assertSame(['SUCCESS', 'REJECTED', 'REJECTED'], AuditLog::query()->orderBy('id')->pluck('result')->all());

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($this->detail())
            ->patch(route('admin.listings.deactivate', $this->listing), ['reason' => self::ALASAN])
            ->assertOk()
            ->assertSee(__('listing-moderation.errors.already_hidden'));
    }

    public function test_listing_draf_lewat_patch_dialihkan_ke_daftar_dengan_error(): void
    {
        $draf = Listing::factory()->create(['review_status' => Listing::REVIEW_DRAFT]);

        $this->actingAs($this->admin)
            ->patch(route('admin.listings.deactivate', $draf), ['reason' => self::ALASAN])
            ->assertRedirect(route('admin.listings.index'))
            ->assertSessionHas('error', __('listing-moderation.errors.draft'));

        $this->assertSame('VISIBLE', $this->statusDb($draf));
        $this->assertSame('REJECTED', AuditLog::sole()->result);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->patch(route('admin.listings.deactivate', $draf), ['reason' => self::ALASAN])
            ->assertOk()
            ->assertSee(__('listing-moderation.errors.draft'));
    }

    public function test_route_aksi_hanya_menerima_patch(): void
    {
        foreach (['deactivate', 'restore'] as $aksi) {
            $url = route('admin.listings.'.$aksi, $this->listing);

            $this->actingAs($this->admin)->get($url)->assertStatus(405);
            $this->actingAs($this->admin)->post($url, ['reason' => self::ALASAN])->assertStatus(405);
            $this->actingAs($this->admin)->put($url, ['reason' => self::ALASAN])->assertStatus(405);
            $this->actingAs($this->admin)->delete($url)->assertStatus(405);
        }

        $this->assertSame('VISIBLE', $this->statusDb());
        $this->assertDatabaseCount('listing_moderations', 0);
    }

    public function test_tidak_ada_route_ubah_atau_hapus_riwayat(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteDefinition $route) => str_starts_with($route->uri(), 'admin/listing'));

        $this->assertEqualsCanonicalizing(
            ['admin.listings.index', 'admin.listings.moderations', 'admin.listings.show', 'admin.listings.deactivate', 'admin.listings.restore'],
            $routes->map(fn (RouteDefinition $route) => $route->getName())->values()->all()
        );
        $this->assertEmpty($routes->filter(fn (RouteDefinition $route) => array_intersect(['PUT', 'DELETE', 'POST'], $route->methods()) !== []));

        $moderation = ListingModeration::factory()->forListing($this->listing)->create();

        foreach (['put', 'delete', 'patch', 'post'] as $method) {
            $this->actingAs($this->admin)->{$method}(route('admin.listings.moderations'))->assertStatus(405);
        }

        $this->actingAs($this->admin)->delete(route('admin.listings.show', $this->listing))->assertStatus(405);
        $this->actingAs($this->admin)->put(route('admin.listings.show', $this->listing))->assertStatus(405);
        $this->assertModelExists($moderation);
    }

    public function test_alasan_moderasi_tidak_tampil_di_halaman_publik(): void
    {
        $warga = User::factory()->create();

        $this->kirim('deactivate', ['reason' => 'Alasan rahasia penonaktifan XYZ123'])->assertSessionHasNoErrors();
        $this->kirim('restore', ['reason' => 'Alasan rahasia pemulihan ABC789'])->assertSessionHasNoErrors();
        $this->assertSame(ListingModerationStatus::Visible, $this->listing->fresh()->moderation_status);

        foreach ([null, $warga] as $pengunjung) {
            foreach ([route('catalog.index'), route('catalog.show', $this->listing->id)] as $url) {
                $respons = $pengunjung ? $this->actingAs($pengunjung)->get($url) : $this->get($url);

                $respons->assertOk()
                    ->assertSee('Lemari Pakaian Jati')
                    ->assertDontSee('XYZ123')
                    ->assertDontSee('ABC789');
            }
        }
    }
}
