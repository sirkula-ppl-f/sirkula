<?php

namespace Tests\Feature\Admin;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengelolaan panduan dan edukasi oleh admin (PBI-18): daftar, buat, ubah, dan validasi.
 */
class GuideAdminTest extends TestCase
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Satuan lot untuk material',
            'body' => 'Cantumkan berat total per lot.',
            'status' => 'published',
            'placements' => ['listing_form', 'detail_page'],
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.guides.index'))->assertRedirect(route('login'));
    }

    public function test_warga_biasa_ditolak_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.guides.index'))
            ->assertForbidden();
    }

    public function test_admin_melihat_daftar_panduan_beserta_lokasinya(): void
    {
        Guide::factory()->forPlacements('listing_form', 'detail_page')->create([
            'title' => 'Satuan lot untuk material',
            'slug' => 'satuan-lot',
        ]);
        Guide::factory()->draft()->forPlacements('dashboard')->create([
            'title' => 'Menjual dengan bertanggung jawab',
            'slug' => 'menjual',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.guides.index'))
            ->assertOk()
            ->assertSee('Panduan dan Edukasi')
            ->assertSeeInOrder(['dusk="guide-row-satuan-lot"', 'Satuan lot untuk material', 'Form listing, Halaman detail', 'Terbit'], false)
            ->assertSeeInOrder(['dusk="guide-row-menjual"', 'Menjual dengan bertanggung jawab', 'Dashboard', 'Draf'], false)
            ->assertSee('dusk="edit-guide-satuan-lot"', false);
    }

    public function test_admin_membuat_panduan_dengan_dua_lokasi_dan_angka_mulai_dari_nol(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.guides.create'))
            ->post(route('admin.guides.store'), $this->data())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.guides.index'))
            ->assertSessionHas('status', 'Panduan dibuat.');

        $guide = Guide::where('title', 'Satuan lot untuk material')->firstOrFail();

        $this->assertSame('published', $guide->status);
        $this->assertSame(0, $guide->views_count);
        $this->assertSame(0, $guide->interactions_count);
        $this->assertEqualsCanonicalizing(['listing_form', 'detail_page'], $guide->placement_keys);
    }

    public function test_lokasi_tampil_wajib_diisi(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.guides.create'))
            ->post(route('admin.guides.store'), $this->data(['placements' => []]))
            ->assertRedirect(route('admin.guides.create'))
            ->assertSessionHasErrors(['placements' => 'Pilih minimal satu lokasi tampil.']);

        $this->assertDatabaseCount('guides', 0);
        $this->assertDatabaseCount('guide_placements', 0);
    }

    public function test_judul_dan_isi_wajib_serta_dibatasi_panjangnya(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.guides.store'), $this->data(['title' => '', 'body' => '']))
            ->assertSessionHasErrors(['title', 'body']);

        $this->actingAs($this->admin)
            ->post(route('admin.guides.store'), $this->data([
                'title' => str_repeat('a', 121),
                'body' => str_repeat('b', 501),
            ]))
            ->assertSessionHasErrors(['title', 'body']);

        $this->assertDatabaseCount('guides', 0);
    }

    public function test_status_dan_lokasi_yang_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.guides.store'), $this->data([
                'status' => 'rahasia',
                'placements' => ['tidak_ada'],
            ]))
            ->assertSessionHasErrors(['status', 'placements.0' => 'Lokasi tampil yang dipilih tidak valid.']);

        $this->assertDatabaseCount('guides', 0);
    }

    public function test_admin_mengubah_panduan_dan_lokasinya_tanpa_mengubah_slug(): void
    {
        $guide = Guide::factory()->draft()->forPlacements('listing_form', 'detail_page')->create([
            'title' => 'Judul lama',
            'slug' => 'judul-lama',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.guides.edit', $guide))
            ->put(route('admin.guides.update', $guide), $this->data([
                'title' => 'Judul baru',
                'status' => 'published',
                'placements' => ['dashboard'],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.guides.index'))
            ->assertSessionHas('status', 'Panduan diperbarui.');

        $guide->refresh()->load('placements');

        $this->assertSame('Judul baru', $guide->title);
        $this->assertSame('published', $guide->status);
        $this->assertSame('judul-lama', $guide->slug);
        $this->assertSame(['dashboard'], $guide->placement_keys);
        $this->assertDatabaseCount('guide_placements', 1);
    }

    public function test_angka_tayang_dan_interaksi_tidak_bisa_diisi_lewat_form(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.guides.store'), $this->data(['views_count' => 9999, 'interactions_count' => 9999]))
            ->assertSessionHasNoErrors();

        $guide = Guide::firstOrFail();
        $this->assertSame(0, $guide->views_count);
        $this->assertSame(0, $guide->interactions_count);

        $this->actingAs($this->admin)
            ->put(route('admin.guides.update', $guide), $this->data(['views_count' => 9999, 'interactions_count' => 9999]))
            ->assertSessionHasNoErrors();

        $guide->refresh();
        $this->assertSame(0, $guide->views_count);
        $this->assertSame(0, $guide->interactions_count);
    }

    public function test_halaman_ubah_menampilkan_lokasi_yang_sudah_dipilih(): void
    {
        $guide = Guide::factory()->forPlacements('detail_page')->create(['title' => 'Panduan detail']);

        $this->actingAs($this->admin)
            ->get(route('admin.guides.edit', $guide))
            ->assertOk()
            ->assertSee('Panduan detail')
            ->assertSeeInOrder(['value="detail_page"', 'checked'], false);
    }

    public function test_daftar_panduan_tidak_menambah_query_per_baris(): void
    {
        $url = route('admin.guides.index');

        Guide::factory()->count(2)->forPlacements('listing_form', 'dashboard')->create();

        $this->jumlahQuery($url);
        $denganDuaPanduan = $this->jumlahQuery($url);

        Guide::factory()->count(13)->forPlacements('listing_form', 'detail_page')->create();

        $this->assertSame($denganDuaPanduan, $this->jumlahQuery($url), 'Jumlah query halaman daftar harus sama untuk 2 dan 15 panduan.');
    }
}
