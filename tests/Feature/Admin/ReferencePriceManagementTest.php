<?php

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReferencePriceManagementTest extends TestCase
{
    use RefreshDatabase;

    private Material $material;

    private Region $provinsi;

    protected function setUp(): void
    {
        parent::setUp();

        // 12.00 WIB, 11 November 2026. Tanggal berlaku paling lambat 9 Feb 2027.
        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));

        $this->material = Material::factory()->create(['name' => 'Plastik PET']);
        $this->provinsi = Region::create(['name' => 'Provinsi Contoh', 'code' => 'P1']);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function dataValid(array $override = []): array
    {
        return array_merge([
            'material_id' => (string) $this->material->id,
            'region_id' => (string) $this->provinsi->id,
            'price_per_kg' => '3500',
            'effective_date' => '2026-11-11',
            'source' => 'Survei harga pengepul',
        ], $override);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function semuaRoute(): array
    {
        return [
            'riwayat' => ['get', 'admin.reference-prices.index'],
            'form terbitkan' => ['get', 'admin.reference-prices.create'],
            'terbitkan' => ['post', 'admin.reference-prices.store'],
        ];
    }

    #[DataProvider('semuaRoute')]
    public function test_warga_mendapat_403(string $method, string $route): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}(route($route), $this->dataValid())
            ->assertForbidden();

        $this->assertDatabaseCount('reference_prices', 0);
    }

    #[DataProvider('semuaRoute')]
    public function test_tamu_diarahkan_ke_login(string $method, string $route): void
    {
        $this->{$method}(route($route), $this->dataValid())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reference_prices', 0);
    }

    #[DataProvider('semuaRoute')]
    public function test_admin_yang_ditangguhkan_dikeluarkan(string $method, string $route): void
    {
        $this->actingAs(User::factory()->admin()->suspended()->create())
            ->{$method}(route($route), $this->dataValid())
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_url_sesuai_kontrak(): void
    {
        $this->assertSame(url('/admin/harga-acuan'), route('admin.reference-prices.index'));
        $this->assertSame(url('/admin/harga-acuan/terbitkan'), route('admin.reference-prices.create'));
        $this->assertSame(url('/admin/harga-acuan'), route('admin.reference-prices.store'));
    }

    public function test_tidak_ada_route_ubah_atau_hapus(): void
    {
        foreach (['edit', 'update', 'destroy', 'show'] as $aksi) {
            $this->assertFalse(Route::has("admin.reference-prices.{$aksi}"), "Route admin.reference-prices.{$aksi} tidak boleh ada.");
        }

        $harga = ReferencePrice::factory()->create(['price_per_kg' => 3000]);
        $admin = $this->admin();

        $percobaan = [
            ['put', "/admin/harga-acuan/{$harga->id}"],
            ['patch', "/admin/harga-acuan/{$harga->id}"],
            ['delete', "/admin/harga-acuan/{$harga->id}"],
            ['put', '/admin/harga-acuan'],
            ['patch', '/admin/harga-acuan'],
            ['delete', '/admin/harga-acuan'],
            ['get', "/admin/harga-acuan/{$harga->id}/ubah"],
        ];

        foreach ($percobaan as [$method, $url]) {
            $status = $this->actingAs($admin)->{$method}($url, ['price_per_kg' => 1])->getStatusCode();

            $this->assertContains($status, [404, 405], strtoupper($method)." {$url} seharusnya 404/405, dapat {$status}.");
        }

        $this->assertDatabaseCount('reference_prices', 1);
        $this->assertSame(3000, $harga->fresh()->price_per_kg);
    }

    public function test_halaman_terbitkan_berisi_form_yang_aksesibel(): void
    {
        Material::factory()->inactive()->create(['name' => 'Material Lama']);
        Region::create(['name' => 'Kota Contoh', 'code' => 'P1.01', 'parent_id' => $this->provinsi->id]);
        Region::create(['name' => 'Aceh Contoh', 'code' => 'P0']);

        $this->actingAs($this->admin())
            ->get(route('admin.reference-prices.create'))
            ->assertOk()
            ->assertSee('Terbitkan Harga Acuan')
            ->assertSee('action="'.route('admin.reference-prices.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-submit-once', false)
            ->assertSee('data-confirm="Harga acuan yang diterbitkan tidak dapat diubah atau dihapus.', false)
            ->assertSee('data-confirm-title="Terbitkan harga acuan?"', false)
            ->assertSee('data-confirm-dialog', false)
            ->assertSee('<label for="material_id"', false)
            ->assertSee('<label for="region_id"', false)
            ->assertSee('<label for="price_per_kg"', false)
            ->assertSee('<label for="effective_date"', false)
            ->assertSee('<label for="source"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('aria-describedby="price_per_kg-hint"', false)
            ->assertSee('type="date"', false)
            ->assertSee('value="2026-11-11"', false)
            ->assertSee('min="2020-01-01"', false)
            ->assertSee('max="2027-02-09"', false)
            ->assertSee('maxlength="255"', false)
            ->assertSee('Plastik PET')
            ->assertDontSee('Material Lama')
            ->assertSeeInOrder(['Pilih provinsi', 'Aceh Contoh', 'Provinsi Contoh'])
            ->assertDontSee('Kota Contoh')
            ->assertDontSee('name="published_by"', false)
            ->assertDontSee('dusk="current-price"', false)
            ->assertSee('dusk="publish-button"', false)
            ->assertSee('href="'.route('admin.reference-prices.index').'"', false);
    }

    public function test_tanggal_bawaan_mengikuti_hari_ini_wib(): void
    {
        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12.
        $this->travelTo(Carbon::parse('2026-11-11 23:30:00', 'UTC'));

        $this->actingAs($this->admin())
            ->get(route('admin.reference-prices.create'))
            ->assertOk()
            ->assertSee('value="2026-11-12"', false)
            ->assertSee('max="2027-02-10"', false);
    }

    public function test_admin_menerbitkan_harga_dan_penerbit_dari_user_login(): void
    {
        $admin = $this->admin();
        $lain = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.reference-prices.store'), $this->dataValid([
                'source' => '  Survei harga pengepul  ',
                'published_by' => (string) $lain->id,
            ]))
            ->assertRedirect(route('admin.reference-prices.index'))
            ->assertSessionHas('status', 'Harga acuan Plastik PET di Provinsi Contoh sebesar Rp 3.500/kg berhasil diterbitkan, berlaku sejak 11 Nov 2026.');

        $this->assertDatabaseCount('reference_prices', 1);
        $this->assertDatabaseHas('reference_prices', [
            'material_id' => $this->material->id,
            'region_id' => $this->provinsi->id,
            'price_per_kg' => 3500,
            'effective_date' => '2026-11-11',
            'source' => 'Survei harga pengepul',
            'published_by' => $admin->id,
        ]);
    }

    public function test_harga_bertanggal_setelah_hari_ini_diterbitkan_sebagai_terjadwal(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.reference-prices.store'), $this->dataValid(['effective_date' => '2026-12-01']))
            ->assertRedirect(route('admin.reference-prices.index'))
            ->assertSessionHas('status', 'Harga acuan Plastik PET di Provinsi Contoh sebesar Rp 3.500/kg berhasil diterbitkan dan terjadwal berlaku mulai 1 Des 2026.');

        $this->assertDatabaseHas('reference_prices', ['effective_date' => '2026-12-01']);
    }

    public function test_koreksi_dengan_tanggal_sama_menjadi_entri_baru(): void
    {
        $admin = $this->admin();
        $lama = ReferencePrice::factory()->forMaterial($this->material)->forRegion($this->provinsi)
            ->effectiveOn('2026-11-11')->create(['price_per_kg' => 35000]);

        $this->actingAs($admin)
            ->post(route('admin.reference-prices.store'), $this->dataValid(['price_per_kg' => '3500']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reference_prices', 2);
        $this->assertSame(35000, $lama->fresh()->price_per_kg);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function dataTidakValid(): array
    {
        return [
            'material kosong' => [['material_id' => ''], 'material_id', 'Pilih material.'],
            'material bukan angka' => [['material_id' => 'plastik'], 'material_id', 'Pilih material.'],
            'material tidak ada' => [['material_id' => '999999'], 'material_id', 'Pilih material yang masih aktif.'],
            'provinsi kosong' => [['region_id' => ''], 'region_id', 'Pilih provinsi.'],
            'provinsi bukan angka' => [['region_id' => 'P1'], 'region_id', 'Pilih provinsi dari daftar. Harga acuan hanya diterbitkan per provinsi.'],
            'provinsi tidak ada' => [['region_id' => '999999'], 'region_id', 'Pilih provinsi dari daftar. Harga acuan hanya diterbitkan per provinsi.'],
            'harga kosong' => [['price_per_kg' => ''], 'price_per_kg', 'Harga per kg wajib diisi.'],
            'harga nol' => [['price_per_kg' => '0'], 'price_per_kg', 'Harga per kg minimal Rp 1.'],
            'harga negatif' => [['price_per_kg' => '-500'], 'price_per_kg', 'Harga per kg minimal Rp 1.'],
            'harga desimal' => [['price_per_kg' => '3500.5'], 'price_per_kg', 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.'],
            'harga bertitik ribuan' => [['price_per_kg' => '3.500'], 'price_per_kg', 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.'],
            'harga berkoma' => [['price_per_kg' => '3500,5'], 'price_per_kg', 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.'],
            'harga berupa teks' => [['price_per_kg' => 'tiga ribu'], 'price_per_kg', 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.'],
            'harga berupa array' => [['price_per_kg' => ['3500']], 'price_per_kg', 'Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.'],
            'harga terlalu besar' => [['price_per_kg' => '10000001'], 'price_per_kg', 'Harga per kg paling besar Rp 10.000.000.'],
            'tanggal kosong' => [['effective_date' => ''], 'effective_date', 'Tanggal berlaku wajib diisi.'],
            'tanggal format lain' => [['effective_date' => '11/11/2026'], 'effective_date', 'Tanggal berlaku harus berupa tanggal yang valid.'],
            'tanggal tidak ada di kalender' => [['effective_date' => '2026-02-30'], 'effective_date', 'Tanggal berlaku harus berupa tanggal yang valid.'],
            'tanggal sebelum 2020' => [['effective_date' => '2019-12-31'], 'effective_date', 'Tanggal berlaku paling awal 1 Jan 2020.'],
            'tanggal lebih dari 90 hari' => [['effective_date' => '2027-02-10'], 'effective_date', 'Tanggal berlaku paling lambat 9 Feb 2027 (90 hari dari hari ini).'],
            'sumber kosong' => [['source' => ''], 'source', 'Sumber harga wajib diisi.'],
            'sumber hanya spasi' => [['source' => '    '], 'source', 'Sumber harga wajib diisi.'],
            'sumber berupa array' => [['source' => ['Survei']], 'source', 'Sumber harga harus berupa teks.'],
            'sumber lebih dari 255 karakter' => [['source' => str_repeat('a', 256)], 'source', 'Sumber harga maksimal 255 karakter.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('dataTidakValid')]
    public function test_validasi_per_field(array $override, string $field, string $pesan): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.reference-prices.create'))
            ->post(route('admin.reference-prices.store'), $this->dataValid($override));

        $response->assertRedirect(route('admin.reference-prices.create'))
            ->assertSessionHasErrors([$field => $pesan]);

        foreach (array_diff(['material_id', 'region_id', 'price_per_kg', 'effective_date', 'source'], [$field]) as $lainnya) {
            $response->assertSessionDoesntHaveErrors($lainnya);
        }

        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_material_nonaktif_ditolak(): void
    {
        $nonaktif = Material::factory()->inactive()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.reference-prices.store'), $this->dataValid(['material_id' => (string) $nonaktif->id]))
            ->assertSessionHasErrors(['material_id' => 'Pilih material yang masih aktif.']);

        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_wilayah_kab_kota_ditolak_karena_bukan_provinsi(): void
    {
        $kota = Region::create(['name' => 'Kota Contoh', 'code' => 'P1.01', 'parent_id' => $this->provinsi->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.reference-prices.store'), $this->dataValid(['region_id' => (string) $kota->id]))
            ->assertSessionHasErrors(['region_id' => 'Pilih provinsi dari daftar. Harga acuan hanya diterbitkan per provinsi.']);

        $this->assertDatabaseCount('reference_prices', 0);
    }

    public function test_nilai_batas_diterima(): void
    {
        $admin = $this->admin();

        foreach ([
            ['price_per_kg' => '1', 'effective_date' => '2020-01-01'],
            ['price_per_kg' => '10000000', 'effective_date' => '2027-02-09'],
            ['source' => str_repeat('s', 255)],
        ] as $override) {
            $this->actingAs($admin)
                ->post(route('admin.reference-prices.store'), $this->dataValid($override))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('reference_prices', 3);
    }

    public function test_nilai_lama_dan_harga_yang_berlaku_tampil_saat_validasi_gagal(): void
    {
        ReferencePrice::factory()->forMaterial($this->material)->forRegion($this->provinsi)
            ->effectiveOn('2026-11-01')->create(['price_per_kg' => 3000, 'source' => 'Laporan bulanan']);
        ReferencePrice::factory()->forMaterial($this->material)->forRegion($this->provinsi)->scheduled()->create(['price_per_kg' => 9900]);

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.reference-prices.create'))
            ->post(route('admin.reference-prices.store'), $this->dataValid([
                'price_per_kg' => '3.200',
                'effective_date' => '2026-11-05',
                'source' => 'Sumber yang sudah diketik',
            ]))
            ->assertOk()
            ->assertSee('Harga per kg harus berupa rupiah bulat, tanpa titik, koma, atau desimal.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="price_per_kg-hint price_per_kg-error"', false)
            ->assertSee('dusk="current-price"', false)
            ->assertSee('Harga yang sedang berlaku')
            ->assertSee('Plastik PET di Provinsi Contoh: Rp 3.000/kg, berlaku sejak 1 Nov 2026. Sumber: Laporan bulanan.')
            ->assertDontSee('Rp 9.900/kg')
            ->assertSee('<option value="'.$this->material->id.'" selected', false)
            ->assertSee('<option value="'.$this->provinsi->id.'" selected', false)
            ->assertSee('value="3.200"', false)
            ->assertSee('value="2026-11-05"', false)
            ->assertSee('value="Sumber yang sudah diketik"', false);

        $this->assertDatabaseCount('reference_prices', 2);
    }

    public function test_pasangan_tanpa_harga_berlaku_ditandai_belum_ada(): void
    {
        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.reference-prices.create'))
            ->post(route('admin.reference-prices.store'), $this->dataValid(['source' => '']))
            ->assertOk()
            ->assertSee('Belum ada harga acuan yang berlaku untuk Plastik PET di Provinsi Contoh.');
    }

    public function test_pilihan_tidak_valid_tidak_menampilkan_harga_berlaku(): void
    {
        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.reference-prices.create'))
            ->post(route('admin.reference-prices.store'), $this->dataValid(['region_id' => '999999']))
            ->assertOk()
            ->assertDontSee('dusk="current-price"', false);
    }
}
