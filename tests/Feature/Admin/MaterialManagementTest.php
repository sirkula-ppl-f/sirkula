<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase, TabelPemakaianSementara;

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
            'name' => 'Plastik PET',
            'description' => 'Botol minuman bening.',
        ], $override);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function semuaRoute(): array
    {
        return [
            'daftar' => ['get', 'admin.materials.index', false],
            'form tambah' => ['get', 'admin.materials.create', false],
            'simpan' => ['post', 'admin.materials.store', false],
            'form ubah' => ['get', 'admin.materials.edit', true],
            'perbarui' => ['put', 'admin.materials.update', true],
            'hapus' => ['delete', 'admin.materials.destroy', true],
            'nonaktifkan' => ['patch', 'admin.materials.deactivate', true],
            'aktifkan' => ['patch', 'admin.materials.activate', true],
        ];
    }

    private function urlRoute(string $route, bool $butuhMaterial, Material $material): string
    {
        return $butuhMaterial ? route($route, $material) : route($route);
    }

    #[DataProvider('semuaRoute')]
    public function test_warga_mendapat_403_di_semua_route(string $method, string $route, bool $butuhMaterial): void
    {
        $material = Material::factory()->create(['name' => 'Logam']);

        $this->actingAs(User::factory()->create())
            ->{$method}($this->urlRoute($route, $butuhMaterial, $material), $this->dataValid())
            ->assertForbidden();

        $this->assertDatabaseCount('materials', 1);
        $this->assertSame('Logam', $material->fresh()->name);
        $this->assertTrue($material->fresh()->is_active);
    }

    #[DataProvider('semuaRoute')]
    public function test_tamu_diarahkan_ke_login_di_semua_route(string $method, string $route, bool $butuhMaterial): void
    {
        $material = Material::factory()->create();

        $this->{$method}($this->urlRoute($route, $butuhMaterial, $material), $this->dataValid())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('materials', 1);
    }

    public function test_url_material_sesuai_kontrak(): void
    {
        $material = Material::factory()->create();

        $this->assertSame(url('/admin/material'), route('admin.materials.index'));
        $this->assertSame(url('/admin/material/tambah'), route('admin.materials.create'));
        $this->assertSame(url("/admin/material/{$material->id}/ubah"), route('admin.materials.edit', $material));
        $this->assertSame(url("/admin/material/{$material->id}/nonaktifkan"), route('admin.materials.deactivate', $material));
        $this->assertSame(url("/admin/material/{$material->id}/aktifkan"), route('admin.materials.activate', $material));
    }

    public function test_halaman_tambah_berisi_form_yang_aksesibel(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.materials.create'))
            ->assertOk()
            ->assertSee('Tambah Material')
            ->assertSee('action="'.route('admin.materials.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-submit-once', false)
            ->assertSee('<label for="name"', false)
            ->assertSee('<label for="description"', false)
            ->assertSee('aria-describedby="description-hint"', false)
            ->assertDontSee('listing_types', false)
            ->assertSee('dusk="save-button"', false)
            ->assertSee('href="'.route('admin.materials.index').'"', false);
    }

    public function test_admin_menambah_material(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->dataValid(['name' => '  Plastik   PET ']))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('status', 'Material "Plastik PET" berhasil ditambahkan.');

        $material = Material::sole();
        $this->assertSame('Plastik PET', $material->name);
        $this->assertSame('plastik-pet', $material->slug);
        $this->assertSame('Botol minuman bening.', $material->description);
        $this->assertTrue($material->is_active);
    }

    public function test_deskripsi_hanya_dirapikan_di_awal_dan_akhir_sehingga_baris_baru_tetap(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->dataValid([
                'description' => "  Botol bening.\nTutup dilepas.  ",
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame("Botol bening.\nTutup dilepas.", Material::sole()->description);
    }

    public function test_slug_unik_ditambah_angka_bila_bentrok(): void
    {
        Material::factory()->create(['name' => 'Kertas Lama', 'slug' => 'kertas']);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->dataValid(['name' => 'Kertas']))
            ->assertRedirect(route('admin.materials.index'));

        $this->assertSame('kertas-2', Material::where('name', 'Kertas')->sole()->slug);
    }

    public function test_halaman_ubah_menampilkan_nilai_tersimpan(): void
    {
        $material = Material::factory()->create([
            'name' => 'Kaca',
            'slug' => 'kaca',
            'description' => 'Botol kaca bekas.',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.materials.edit', $material))
            ->assertOk()
            ->assertSee('Ubah Material')
            ->assertSee('value="Kaca"', false)
            ->assertSee('>Botol kaca bekas.</textarea>', false)
            ->assertSee('action="'.route('admin.materials.update', $material).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSeeInOrder(['Slug', 'kaca', '(tidak berubah)']);
    }

    public function test_admin_mengubah_material_dan_slug_tidak_berubah(): void
    {
        $material = Material::factory()->create(['name' => 'Kertas', 'slug' => 'kertas']);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), $this->dataValid([
                'name' => 'Kertas dan Kardus',
                'description' => '',
            ]))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('status', 'Material "Kertas dan Kardus" berhasil diperbarui.');

        $material->refresh();
        $this->assertSame('Kertas dan Kardus', $material->name);
        $this->assertSame('kertas', $material->slug);
        $this->assertNull($material->description);
    }

    public function test_mengubah_huruf_kapital_nama_sendiri_diterima(): void
    {
        $material = Material::factory()->create(['name' => 'kaca']);

        $this->actingAs($this->admin())
            ->put(route('admin.materials.update', $material), $this->dataValid(['name' => 'Kaca']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.materials.index'));

        $this->assertSame('Kaca', $material->fresh()->name);
    }

    public function test_nama_duplikat_beda_huruf_kapital_ditolak_saat_tambah_dan_ubah(): void
    {
        $admin = $this->admin();
        Material::factory()->create(['name' => 'Logam']);
        $lain = Material::factory()->create(['name' => 'Kaca']);

        foreach (['LOGAM', '  logam  '] as $nama) {
            $this->actingAs($admin)
                ->from(route('admin.materials.create'))
                ->post(route('admin.materials.store'), $this->dataValid(['name' => $nama]))
                ->assertRedirect(route('admin.materials.create'))
                ->assertSessionHasErrors(['name' => 'Nama sudah digunakan.']);
        }

        $this->actingAs($admin)
            ->from(route('admin.materials.edit', $lain))
            ->put(route('admin.materials.update', $lain), $this->dataValid(['name' => 'lOgAm']))
            ->assertRedirect(route('admin.materials.edit', $lain))
            ->assertSessionHasErrors(['name' => 'Nama sudah digunakan.']);

        $this->assertDatabaseCount('materials', 2);
        $this->assertSame('Kaca', $lain->fresh()->name);
    }

    public function test_nama_sama_dengan_kategori_tetap_diterima(): void
    {
        Category::factory()->create(['name' => 'Kertas']);

        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->dataValid(['name' => 'Kertas']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('materials', ['name' => 'Kertas']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function dataTidakValid(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name', 'Nama wajib diisi.'],
            'nama hanya spasi' => [['name' => '    '], 'name', 'Nama wajib diisi.'],
            'nama bukan teks' => [['name' => ['Logam']], 'name', 'Nama harus berupa teks.'],
            'nama lebih dari 100 karakter' => [['name' => str_repeat('a', 101)], 'name', 'Nama tidak boleh lebih dari 100 karakter.'],
            'deskripsi lebih dari 500 karakter' => [['description' => str_repeat('a', 501)], 'description', 'Deskripsi tidak boleh lebih dari 500 karakter.'],
            'deskripsi bukan teks' => [['description' => ['a']], 'description', 'Deskripsi harus berupa teks.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('dataTidakValid')]
    public function test_validasi_per_field(array $override, string $field, string $pesan): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.materials.create'))
            ->post(route('admin.materials.store'), $this->dataValid($override));

        $response->assertRedirect(route('admin.materials.create'))
            ->assertSessionHasErrors([$field => $pesan]);

        foreach (array_diff(['name', 'description'], [$field]) as $lainnya) {
            $response->assertSessionDoesntHaveErrors($lainnya);
        }

        $this->assertDatabaseCount('materials', 0);
    }

    public function test_nama_100_dan_deskripsi_500_karakter_diterima(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.materials.store'), $this->dataValid([
                'name' => str_repeat('a', 100),
                'description' => str_repeat('b', 500),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('materials', 1);
    }

    public function test_nilai_lama_terjaga_saat_validasi_gagal(): void
    {
        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.materials.create'))
            ->post(route('admin.materials.store'), [
                'name' => '',
                'description' => 'Deskripsi yang sudah diketik.',
            ])
            ->assertOk()
            ->assertSee('Nama wajib diisi.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="name-error"', false)
            ->assertSee('>Deskripsi yang sudah diketik.</textarea>', false);

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.materials.create'))
            ->post(route('admin.materials.store'), [
                'name' => 'Logam',
                'description' => str_repeat('a', 501),
            ])
            ->assertOk()
            ->assertSee('value="Logam"', false)
            ->assertSee('aria-describedby="description-hint description-error"', false);
    }

    public function test_admin_menonaktifkan_dan_mengaktifkan_material(): void
    {
        $admin = $this->admin();
        $material = Material::factory()->create(['name' => 'Logam']);
        $daftar = route('admin.materials.index', ['status' => 'aktif']);

        $this->actingAs($admin)
            ->from($daftar)
            ->patch(route('admin.materials.deactivate', $material))
            ->assertRedirect($daftar)
            ->assertSessionHas('status', 'Material "Logam" dinonaktifkan. Listing baru tidak dapat memakainya.');

        $this->assertFalse($material->fresh()->is_active);

        $this->actingAs($admin)
            ->from($daftar)
            ->patch(route('admin.materials.activate', $material))
            ->assertRedirect($daftar)
            ->assertSessionHas('status', 'Material "Logam" diaktifkan kembali.');

        $this->assertTrue($material->fresh()->is_active);
    }

    public function test_admin_menghapus_material_yang_belum_dipakai(): void
    {
        $material = Material::factory()->create(['name' => 'Logam']);

        $this->actingAs($this->admin())
            ->from(route('admin.materials.index'))
            ->delete(route('admin.materials.destroy', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('status', 'Material "Logam" berhasil dihapus.');

        $this->assertModelMissing($material);
    }

    public function test_hapus_material_yang_dipakai_listing_ditolak_dengan_flash_error(): void
    {
        $material = Material::factory()->create();
        $this->tambahListing(['material_id' => $material->id]);

        $this->actingAs($this->admin())
            ->from(route('admin.materials.index'))
            ->delete(route('admin.materials.destroy', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('error', 'Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.')
            ->assertSessionMissing('status');

        $this->assertModelExists($material);
    }

    public function test_hapus_material_yang_dipakai_harga_acuan_ditolak_dengan_flash_error(): void
    {
        $material = Material::factory()->create();
        $this->tambahHargaAcuan($material->id);

        $this->actingAs($this->admin())
            ->from(route('admin.materials.index'))
            ->delete(route('admin.materials.destroy', $material))
            ->assertRedirect(route('admin.materials.index'))
            ->assertSessionHas('error', 'Material sudah dipakai listing atau harga acuan; hanya dapat dinonaktifkan.')
            ->assertSessionMissing('status');

        $this->assertModelExists($material);
    }

    public function test_material_yang_dipakai_tetap_bisa_dinonaktifkan(): void
    {
        $material = Material::factory()->create();
        $this->tambahListing(['material_id' => $material->id]);
        $this->tambahHargaAcuan($material->id);

        $this->actingAs($this->admin())
            ->from(route('admin.materials.index'))
            ->patch(route('admin.materials.deactivate', $material))
            ->assertSessionHas('status');

        $this->assertFalse($material->fresh()->is_active);
        $this->assertDatabaseHas('listings', ['material_id' => $material->id]);
        $this->assertDatabaseHas('reference_prices', ['material_id' => $material->id]);
    }

    public function test_material_yang_tidak_ada_mendapat_404(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.materials.edit', 999))
            ->assertNotFound();
    }
}
