<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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
            'name' => 'Elektronik dan Gawai',
            'description' => 'Ponsel dan laptop bekas.',
            'listing_types' => ['PRELOVED'],
        ], $override);
    }

    private function assertTipeTercentang(TestResponse $response, string $value, bool $tercentang = true): void
    {
        $tercentangDiHtml = (bool) preg_match('/<input[^>]*value="'.$value.'"[^>]*\schecked[^>]*>/', $response->getContent());

        $this->assertSame($tercentang, $tercentangDiHtml, "Checkbox {$value} seharusnya ".($tercentang ? 'tercentang.' : 'tidak tercentang.'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function semuaRoute(): array
    {
        return [
            'daftar' => ['get', 'admin.categories.index', false],
            'form tambah' => ['get', 'admin.categories.create', false],
            'simpan' => ['post', 'admin.categories.store', false],
            'form ubah' => ['get', 'admin.categories.edit', true],
            'perbarui' => ['put', 'admin.categories.update', true],
            'hapus' => ['delete', 'admin.categories.destroy', true],
            'nonaktifkan' => ['patch', 'admin.categories.deactivate', true],
            'aktifkan' => ['patch', 'admin.categories.activate', true],
        ];
    }

    private function urlRoute(string $route, bool $butuhKategori, Category $category): string
    {
        return $butuhKategori ? route($route, $category) : route($route);
    }

    #[DataProvider('semuaRoute')]
    public function test_warga_mendapat_403_di_semua_route(string $method, string $route, bool $butuhKategori): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs(User::factory()->create())
            ->{$method}($this->urlRoute($route, $butuhKategori, $category), $this->dataValid())
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 1);
        $this->assertSame('Elektronik', $category->fresh()->name);
        $this->assertTrue($category->fresh()->is_active);
    }

    #[DataProvider('semuaRoute')]
    public function test_tamu_diarahkan_ke_login_di_semua_route(string $method, string $route, bool $butuhKategori): void
    {
        $category = Category::factory()->create();

        $this->{$method}($this->urlRoute($route, $butuhKategori, $category), $this->dataValid())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_halaman_tambah_berisi_form_yang_aksesibel(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('Tambah Kategori')
            ->assertSee('action="'.route('admin.categories.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-submit-once', false)
            ->assertSee('<label for="name"', false)
            ->assertSee('<label for="description"', false)
            ->assertSee('<legend', false)
            ->assertSee('name="listing_types[]"', false)
            ->assertSee('<label for="listing-type-preloved"', false)
            ->assertSee('Barang Bekas')
            ->assertSee('Material Daur Ulang')
            ->assertSee('Produk Upcycle')
            ->assertSee('dusk="save-button"', false);
    }

    public function test_admin_menambah_kategori(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), $this->dataValid([
                'name' => '  Elektronik   dan  Gawai ',
                'listing_types' => ['UPCYCLE', 'PRELOVED'],
            ]))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'Kategori "Elektronik dan Gawai" berhasil ditambahkan.');

        $category = Category::sole();
        $this->assertSame('Elektronik dan Gawai', $category->name);
        $this->assertSame('elektronik-dan-gawai', $category->slug);
        $this->assertSame('Ponsel dan laptop bekas.', $category->description);
        $this->assertTrue($category->is_active);
        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->listingTypes()->all());
    }

    public function test_slug_unik_ditambah_angka_bila_bentrok(): void
    {
        Category::factory()->create(['name' => 'Elektronik Lama', 'slug' => 'elektronik']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), $this->dataValid(['name' => 'Elektronik']))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('elektronik-2', Category::where('name', 'Elektronik')->sole()->slug);
    }

    public function test_halaman_ubah_menampilkan_nilai_tersimpan(): void
    {
        $category = Category::factory()->forTypes(ListingType::Recyclable)->create([
            'name' => 'Botol dan Wadah',
            'slug' => 'botol-dan-wadah',
            'description' => 'Botol plastik bekas.',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Ubah Kategori')
            ->assertSee('value="Botol dan Wadah"', false)
            ->assertSee('>Botol plastik bekas.</textarea>', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSeeInOrder(['Slug', 'botol-dan-wadah', '(tidak berubah)']);

        $this->assertTipeTercentang($response, 'RECYCLABLE');
        $this->assertTipeTercentang($response, 'PRELOVED', false);
    }

    public function test_admin_mengubah_kategori_dan_slug_tidak_berubah(): void
    {
        $category = Category::factory()->forTypes(ListingType::Preloved)->create(['name' => 'Elektronik', 'slug' => 'elektronik']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), $this->dataValid([
                'name' => 'Elektronik dan Gawai',
                'description' => '',
                'listing_types' => ['PRELOVED', 'RECYCLABLE'],
            ]))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'Kategori "Elektronik dan Gawai" berhasil diperbarui.');

        $category->refresh();
        $this->assertSame('Elektronik dan Gawai', $category->name);
        $this->assertSame('elektronik', $category->slug);
        $this->assertNull($category->description);
        $this->assertSame([ListingType::Preloved, ListingType::Recyclable], $category->listingTypes()->all());
    }

    public function test_mengubah_huruf_kapital_nama_sendiri_diterima(): void
    {
        $category = Category::factory()->create(['name' => 'elektronik']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), $this->dataValid(['name' => 'Elektronik']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('Elektronik', $category->fresh()->name);
    }

    public function test_nama_duplikat_beda_huruf_kapital_ditolak_saat_tambah_dan_ubah(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Elektronik']);
        $lain = Category::factory()->create(['name' => 'Buku']);

        foreach (['ELEKTRONIK', '  elektronik  '] as $nama) {
            $this->actingAs($admin)
                ->from(route('admin.categories.create'))
                ->post(route('admin.categories.store'), $this->dataValid(['name' => $nama]))
                ->assertRedirect(route('admin.categories.create'))
                ->assertSessionHasErrors(['name' => 'Nama sudah digunakan.']);
        }

        $this->actingAs($admin)
            ->from(route('admin.categories.edit', $lain))
            ->put(route('admin.categories.update', $lain), $this->dataValid(['name' => 'eLeKtRoNiK']))
            ->assertRedirect(route('admin.categories.edit', $lain))
            ->assertSessionHasErrors(['name' => 'Nama sudah digunakan.']);

        $this->assertDatabaseCount('categories', 2);
        $this->assertSame('Buku', $lain->fresh()->name);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function dataTidakValid(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name', 'Nama wajib diisi.'],
            'nama hanya spasi' => [['name' => '    '], 'name', 'Nama wajib diisi.'],
            'nama lebih dari 100 karakter' => [['name' => str_repeat('a', 101)], 'name', 'Nama tidak boleh lebih dari 100 karakter.'],
            'deskripsi lebih dari 500 karakter' => [['description' => str_repeat('a', 501)], 'description', 'Deskripsi tidak boleh lebih dari 500 karakter.'],
            'tanpa tipe listing' => [['listing_types' => []], 'listing_types', 'Pilih minimal satu tipe listing.'],
            'tipe listing bukan daftar' => [['listing_types' => 'PRELOVED'], 'listing_types', 'Pilih minimal satu tipe listing.'],
            'tipe listing tidak dikenal' => [['listing_types' => ['BARTER']], 'listing_types.0', 'Tipe listing yang dipilih tidak valid.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('dataTidakValid')]
    public function test_validasi_per_field(array $override, string $field, string $pesan): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), $this->dataValid($override));

        $response->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors([$field => $pesan]);

        foreach (array_diff(['name', 'description', 'listing_types'], [$field, 'listing_types.0']) as $lainnya) {
            $response->assertSessionDoesntHaveErrors($lainnya);
        }

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_tanpa_field_tipe_listing_sama_sekali_ditolak(): void
    {
        $data = $this->dataValid();
        unset($data['listing_types']);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), $data)
            ->assertSessionHasErrors(['listing_types' => 'Pilih minimal satu tipe listing.']);
    }

    public function test_nama_tepat_100_karakter_diterima(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), $this->dataValid(['name' => str_repeat('a', 100)]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_nilai_lama_terjaga_saat_validasi_gagal(): void
    {
        $response = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => '',
                'description' => 'Deskripsi yang sudah diketik.',
                'listing_types' => ['UPCYCLE'],
            ])
            ->assertOk()
            ->assertSee('Nama wajib diisi.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('>Deskripsi yang sudah diketik.</textarea>', false);

        $this->assertTipeTercentang($response, 'UPCYCLE');
        $this->assertTipeTercentang($response, 'PRELOVED', false);
    }

    public function test_admin_menonaktifkan_dan_mengaktifkan_kategori(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $daftar = route('admin.categories.index', ['status' => 'aktif']);

        $this->actingAs($admin)
            ->from($daftar)
            ->patch(route('admin.categories.deactivate', $category))
            ->assertRedirect($daftar)
            ->assertSessionHas('status', 'Kategori "Elektronik" dinonaktifkan. Listing baru tidak dapat memakainya.');

        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($admin)
            ->from($daftar)
            ->patch(route('admin.categories.activate', $category))
            ->assertRedirect($daftar)
            ->assertSessionHas('status', 'Kategori "Elektronik" diaktifkan kembali.');

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_admin_menghapus_kategori_yang_belum_dipakai(): void
    {
        $category = Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs($this->admin())
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'Kategori "Elektronik" berhasil dihapus.');

        $this->assertModelMissing($category);
        $this->assertDatabaseCount('category_listing_types', 0);
    }

    public function test_hapus_kategori_yang_dipakai_ditolak_dengan_flash_error(): void
    {
        $category = Category::factory()->create();
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);

        $this->actingAs($this->admin())
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', 'Kategori sudah dipakai listing; hanya dapat dinonaktifkan.')
            ->assertSessionMissing('status');

        $this->assertModelExists($category);
    }

    public function test_kategori_yang_dipakai_tetap_bisa_dinonaktifkan(): void
    {
        $category = Category::factory()->create();
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Preloved->value]);

        $this->actingAs($this->admin())
            ->from(route('admin.categories.index'))
            ->patch(route('admin.categories.deactivate', $category))
            ->assertSessionHas('status');

        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseHas('listings', ['category_id' => $category->id]);
    }

    public function test_menghapus_tipe_listing_yang_dipakai_ditolak_dengan_error_validasi(): void
    {
        $category = Category::factory()->forTypes(ListingType::Preloved, ListingType::Upcycle)->create(['name' => 'Pakaian']);
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);

        $this->actingAs($this->admin())
            ->from(route('admin.categories.edit', $category))
            ->put(route('admin.categories.update', $category), $this->dataValid([
                'name' => 'Pakaian Baru',
                'listing_types' => ['PRELOVED'],
            ]))
            ->assertRedirect(route('admin.categories.edit', $category))
            ->assertSessionHasErrors([
                'listing_types' => 'Tipe listing Produk Upcycle masih dipakai listing dengan kategori ini, jadi tidak dapat dihapus.',
            ])
            ->assertSessionHasInput('name', 'Pakaian Baru');

        $category->refresh();
        $this->assertSame('Pakaian', $category->name);
        $this->assertSame([ListingType::Preloved, ListingType::Upcycle], $category->listingTypes()->all());
    }

    public function test_halaman_ubah_memberi_tahu_tipe_yang_masih_dipakai(): void
    {
        $category = Category::factory()->forTypes(ListingType::Preloved, ListingType::Upcycle)->create();
        $this->tambahListing(['category_id' => $category->id, 'listing_type' => ListingType::Upcycle->value]);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Masih dipakai listing: Produk Upcycle. Tipe ini tidak dapat dihapus dari kategori.')
            ->assertSee('aria-describedby="listing-types-hint listing-types-in-use"', false);
    }

    public function test_kategori_yang_tidak_ada_mendapat_404(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.categories.edit', 999))
            ->assertNotFound();
    }
}
