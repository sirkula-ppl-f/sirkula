<?php

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class MaterialIndexTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function daftar(array $query = []): string
    {
        return route('admin.materials.index', $query);
    }

    private function jumlahQueryDaftar(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get($this->daftar())->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    public function test_menampilkan_data_material_dengan_status_dan_aksi(): void
    {
        Material::factory()->create(['name' => 'Plastik', 'slug' => 'plastik']);
        Material::factory()->inactive()->create(['name' => 'Kaca', 'slug' => 'kaca']);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('dusk="material-row-plastik"', false)
            ->assertSee('dusk="material-row-kaca"', false)
            ->assertSeeInOrder(['Kaca', 'kaca', 'Nonaktif', 'Tidak'])
            ->assertSeeInOrder(['Plastik', 'plastik', 'Aktif', 'Tidak'])
            ->assertSee('dusk="activate-kaca"', false)
            ->assertSee('dusk="deactivate-plastik"', false)
            ->assertSee('dusk="delete-plastik"', false)
            ->assertSee('href="'.route('admin.materials.edit', Material::where('slug', 'plastik')->sole()).'"', false)
            ->assertSee('href="'.route('admin.materials.create').'"', false)
            ->assertSee('<th scope="col"', false)
            ->assertDontSee('Tipe listing');
    }

    public function test_wadah_tabel_punya_label_dan_fokus_terlihat(): void
    {
        Material::factory()->count(16)->create();

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="Tabel daftar material"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('focus-visible:outline-2', false)
            ->assertSee('Daftar material, halaman 1 dari 2');
    }

    public function test_material_yang_dipakai_listing_atau_harga_acuan_ditandai_dan_tombol_hapus_disembunyikan(): void
    {
        $diListing = Material::factory()->create(['name' => 'Logam', 'slug' => 'logam']);
        $diHargaAcuan = Material::factory()->create(['name' => 'Kertas', 'slug' => 'kertas']);
        Material::factory()->create(['name' => 'Tekstil', 'slug' => 'tekstil']);
        $this->tambahListing(['material_id' => $diListing->id]);
        $this->tambahHargaAcuan($diHargaAcuan->id);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSeeInOrder(['Kertas', 'Aktif', 'Ya', 'Logam', 'Aktif', 'Ya', 'Tekstil', 'Aktif', 'Tidak'])
            ->assertDontSee('dusk="delete-logam"', false)
            ->assertDontSee('dusk="delete-kertas"', false)
            ->assertSee('dusk="deactivate-logam"', false)
            ->assertSee('dusk="deactivate-kertas"', false)
            ->assertSee('dusk="delete-tekstil"', false);
    }

    public function test_kolom_dipakai_tidak_menambah_query_per_baris(): void
    {
        $this->buatTabelListings();
        $this->buatTabelReferencePrices();

        $materials = Material::factory()->count(2)->create();
        $this->tambahListing(['material_id' => $materials[0]->id]);
        $this->tambahHargaAcuan($materials[1]->id);

        $this->jumlahQueryDaftar();
        $denganDuaMaterial = $this->jumlahQueryDaftar();

        $lainnya = Material::factory()->count(13)->create();
        foreach ($lainnya->take(5) as $material) {
            $this->tambahListing(['material_id' => $material->id]);
            $this->tambahHargaAcuan($material->id);
        }

        $this->assertSame($denganDuaMaterial, $this->jumlahQueryDaftar(), 'Jumlah query halaman daftar harus sama untuk 2 dan 15 material.');
    }

    public function test_pencarian_memperlakukan_persen_dan_garis_bawah_sebagai_huruf_biasa(): void
    {
        foreach (['Kadar 50%', 'Kadar 500', 'Kode_A', 'KodeXA'] as $name) {
            Material::factory()->create(['name' => $name]);
        }

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => '50%']))
            ->assertOk()
            ->assertSee('Kadar 50%')
            ->assertDontSee('Kadar 500')
            ->assertSee('value="50%"', false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => '_']))
            ->assertSee('Kode_A')
            ->assertDontSee('KodeXA');

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'KADAR']))
            ->assertSee('Kadar 50%')
            ->assertSee('Kadar 500');
    }

    public function test_filter_status(): void
    {
        Material::factory()->create(['name' => 'Logam Aktif']);
        Material::factory()->inactive()->create(['name' => 'Kaca Nonaktif']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'nonaktif']))
            ->assertSee('Kaca Nonaktif')
            ->assertDontSee('Logam Aktif')
            ->assertSee('<option value="nonaktif" selected>', false)
            ->assertSee('dusk="material-filter-reset"', false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'aktif', 'cari' => 'logam']))
            ->assertSee('Logam Aktif')
            ->assertDontSee('Kaca Nonaktif')
            ->assertSee('<option value="aktif" selected>', false)
            ->assertSee('value="logam"', false);
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        Material::factory()->create(['name' => 'Logam']);
        Material::factory()->inactive()->create(['name' => 'Kaca']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'dihapus', 'cari' => ['a', 'b']]))
            ->assertOk()
            ->assertSee('Logam')
            ->assertSee('Kaca')
            ->assertDontSee('dusk="material-filter-reset"', false);
    }

    public function test_paginasi_15_per_halaman_dengan_query_string_tetap(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            Material::factory()->create(['name' => sprintf('Bahan %02d', $i)]);
        }

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'Bahan', 'status' => 'aktif']))
            ->assertOk()
            ->assertSee('Bahan 15')
            ->assertDontSee('Bahan 16')
            ->assertSee(e($this->daftar(['cari' => 'Bahan', 'status' => 'aktif', 'page' => 2])), false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'Bahan', 'status' => 'aktif', 'page' => 2]))
            ->assertOk()
            ->assertSee('Bahan 16')
            ->assertSee('Bahan 17')
            ->assertDontSee('Bahan 15')
            ->assertSee('Daftar material, halaman 2 dari 2');
    }

    public function test_hapus_data_terakhir_di_halaman_mengarahkan_ke_halaman_terakhir_yang_berisi(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Material::factory()->create(['name' => sprintf('Bahan %02d', $i)]);
        }
        $terakhir = Material::where('name', 'Bahan 16')->sole();
        $halaman2 = $this->daftar(['status' => 'aktif', 'page' => 2]);

        $this->actingAs($this->admin)
            ->from($halaman2)
            ->delete(route('admin.materials.destroy', $terakhir))
            ->assertRedirect($halaman2);

        $this->actingAs($this->admin)
            ->get($halaman2)
            ->assertRedirect($this->daftar(['status' => 'aktif', 'page' => 1]));

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'aktif', 'page' => 1]))
            ->assertOk()
            ->assertSee('Material "Bahan 16" berhasil dihapus.')
            ->assertSee('Bahan 15');
    }

    public function test_nonaktifkan_data_terakhir_di_halaman_filter_aktif_juga_diarahkan(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Material::factory()->create(['name' => sprintf('Bahan %02d', $i)]);
        }
        $terakhir = Material::where('name', 'Bahan 16')->sole();
        $halaman2 = $this->daftar(['status' => 'aktif', 'page' => 2]);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($halaman2)
            ->patch(route('admin.materials.deactivate', $terakhir))
            ->assertOk()
            ->assertSee('Material "Bahan 16" dinonaktifkan.')
            ->assertSee('Bahan 15');
    }

    public function test_halaman_melebihi_jumlah_data_tanpa_data_tetap_di_halaman_1(): void
    {
        $this->actingAs($this->admin)
            ->get($this->daftar(['page' => 3]))
            ->assertRedirect($this->daftar(['page' => 1]));

        $this->actingAs($this->admin)
            ->get($this->daftar(['page' => 1]))
            ->assertOk()
            ->assertSee('Belum ada material.');
    }

    public function test_flash_sukses_dan_error_tampil_di_halaman(): void
    {
        $dipakai = Material::factory()->create(['name' => 'Logam']);
        $this->tambahHargaAcuan($dipakai->id);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($this->daftar())
            ->delete(route('admin.materials.destroy', $dipakai))
            ->assertOk()
            ->assertSee('Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.')
            ->assertSee('dusk="alert"', false);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('admin.materials.store'), ['name' => 'Kaca'])
            ->assertOk()
            ->assertSee('Material "Kaca" berhasil ditambahkan.');
    }

    public function test_keadaan_kosong_dengan_dan_tanpa_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Belum ada material.')
            ->assertSee('dusk="material-empty"', false);

        Material::factory()->create(['name' => 'Logam']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada material yang cocok dengan pencarian atau filter.');
    }
}
