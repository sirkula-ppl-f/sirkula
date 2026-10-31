<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\TabelPemakaianSementara;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
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
        return route('admin.categories.index', $query);
    }

    /**
     * Tag pembuka <form> dengan action tertentu.
     */
    private function tagForm(string $html, string $action): string
    {
        $this->assertSame(1, preg_match('/<form\b[^>]*action="'.preg_quote($action, '/').'"[^>]*>/', $html, $cocok), "Form {$action} tidak ditemukan.");

        return $cocok[0];
    }

    public function test_menampilkan_data_kategori_dengan_status_tipe_dan_aksi(): void
    {
        Category::factory()->forTypes(ListingType::Preloved, ListingType::Upcycle)->create(['name' => 'Pakaian', 'slug' => 'pakaian']);
        Category::factory()->inactive()->forTypes(ListingType::Recyclable)->create(['name' => 'Botol', 'slug' => 'botol']);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('dusk="category-row-pakaian"', false)
            ->assertSee('dusk="category-row-botol"', false)
            ->assertSeeInOrder(['Botol', 'botol', 'Material Daur Ulang', 'Nonaktif', 'Tidak'])
            ->assertSeeInOrder(['Pakaian', 'pakaian', 'Barang Bekas', 'Produk Upcycle', 'Aktif', 'Tidak'])
            ->assertSee('dusk="activate-botol"', false)
            ->assertSee('dusk="deactivate-pakaian"', false)
            ->assertSee('dusk="delete-pakaian"', false)
            ->assertSee('href="'.route('admin.categories.create').'"', false)
            ->assertSee('role="region"', false)
            ->assertSee('<th scope="col"', false);
    }

    public function test_wadah_tabel_punya_label_dan_caption_dari_lang_serta_fokus_terlihat(): void
    {
        Category::factory()->count(16)->create();

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="'.__('master-data.categories.table_label').'"', false)
            ->assertSee('aria-label="Tabel daftar kategori"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('focus-visible:outline-2', false)
            ->assertSee('dusk="category-table-region"', false)
            ->assertDontSee('aria-labelledby="judul-tabel-kategori"', false)
            ->assertSee(__('master-data.categories.table_caption', ['page' => 1, 'last' => 2]))
            ->assertSee('Daftar kategori, halaman 1 dari 2');
    }

    public function test_dialog_konfirmasi_dan_atribut_data_confirm_dengan_nama_ter_escape(): void
    {
        $nama = 'Kaca <b> "Bening"';
        $aktif = Category::factory()->create(['name' => $nama, 'slug' => 'kaca-bening']);
        $nonaktif = Category::factory()->inactive()->create(['name' => 'Buku', 'slug' => 'buku']);

        $response = $this->actingAs($this->admin)->get($this->daftar());

        $response->assertOk()
            ->assertSee('dusk="confirm-dialog"', false)
            ->assertSee('<dialog', false)
            ->assertSee('Kaca &lt;b&gt; &quot;Bening&quot;', false)
            ->assertDontSee('Kaca <b>', false);

        $html = $response->getContent();
        $nonaktifkan = $this->tagForm($html, route('admin.categories.deactivate', $aktif));
        $hapus = $this->tagForm($html, route('admin.categories.destroy', $aktif));
        $aktifkan = $this->tagForm($html, route('admin.categories.activate', $nonaktif));

        $this->assertStringContainsString('data-confirm="Nonaktifkan kategori “Kaca &lt;b&gt; &quot;Bening&quot;”? Kategori nonaktif tidak bisa dipilih untuk listing baru, tetapi listing lama tetap tersimpan."', $nonaktifkan);
        $this->assertStringContainsString('data-confirm-title="Nonaktifkan kategori?"', $nonaktifkan);
        $this->assertStringNotContainsString('data-confirm-variant', $nonaktifkan);

        $this->assertStringContainsString('data-confirm="Hapus kategori “Kaca &lt;b&gt; &quot;Bening&quot;”? Tindakan ini tidak bisa dibatalkan."', $hapus);
        $this->assertStringContainsString('data-confirm-variant="danger"', $hapus);

        $this->assertStringNotContainsString('data-confirm', $aktifkan);
    }

    public function test_kategori_yang_dipakai_ditandai_dan_tombol_hapus_disembunyikan(): void
    {
        $dipakai = Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
        Category::factory()->create(['name' => 'Buku', 'slug' => 'buku']);
        $this->tambahListing(['category_id' => $dipakai->id, 'listing_type' => ListingType::Preloved->value]);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSeeInOrder(['Elektronik', 'Aktif', 'Ya'])
            ->assertDontSee('dusk="delete-elektronik"', false)
            ->assertSee('dusk="deactivate-elektronik"', false)
            ->assertSee('dusk="delete-buku"', false);
    }

    public function test_pencarian_memperlakukan_persen_dan_garis_bawah_sebagai_huruf_biasa(): void
    {
        foreach (['Diskon 50%', 'Diskon 500', 'Kode_A', 'KodeXA'] as $name) {
            Category::factory()->create(['name' => $name]);
        }

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => '50%']))
            ->assertOk()
            ->assertSee('Diskon 50%')
            ->assertDontSee('Diskon 500')
            ->assertSee('value="50%"', false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => '_']))
            ->assertSee('Kode_A')
            ->assertDontSee('KodeXA');

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'DISKON']))
            ->assertSee('Diskon 50%')
            ->assertSee('Diskon 500');
    }

    public function test_filter_status_dan_tipe_listing(): void
    {
        Category::factory()->forTypes(ListingType::Preloved)->create(['name' => 'Aktif Preloved']);
        Category::factory()->inactive()->forTypes(ListingType::Preloved)->create(['name' => 'Nonaktif Preloved']);
        Category::factory()->forTypes(ListingType::Recyclable)->create(['name' => 'Aktif Recyclable']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'nonaktif']))
            ->assertSee('Nonaktif Preloved')
            ->assertDontSee('Aktif Preloved')
            ->assertDontSee('Aktif Recyclable')
            ->assertSee('<option value="nonaktif" selected>', false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'aktif', 'tipe' => 'RECYCLABLE']))
            ->assertSee('Aktif Recyclable')
            ->assertDontSee('Aktif Preloved')
            ->assertDontSee('Nonaktif Preloved')
            ->assertSee('<option value="RECYCLABLE" selected>', false)
            ->assertSee('dusk="category-filter-reset"', false);
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        Category::factory()->create(['name' => 'Elektronik']);
        Category::factory()->inactive()->create(['name' => 'Buku']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'dihapus', 'tipe' => 'BARTER', 'cari' => ['a', 'b']]))
            ->assertOk()
            ->assertSee('Elektronik')
            ->assertSee('Buku')
            ->assertDontSee('dusk="category-filter-reset"', false);
    }

    public function test_paginasi_15_per_halaman_dengan_query_string_tetap(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            Category::factory()->create(['name' => sprintf('Kategori %02d', $i)]);
        }

        $halaman1 = $this->actingAs($this->admin)->get($this->daftar(['cari' => 'Kategori', 'status' => 'aktif']));

        $halaman1->assertOk()
            ->assertSee('Kategori 15')
            ->assertDontSee('Kategori 16')
            ->assertSee(e($this->daftar(['cari' => 'Kategori', 'status' => 'aktif', 'page' => 2])), false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'Kategori', 'status' => 'aktif', 'page' => 2]))
            ->assertOk()
            ->assertSee('Kategori 16')
            ->assertSee('Kategori 17')
            ->assertDontSee('Kategori 15');
    }

    public function test_hapus_data_terakhir_di_halaman_mengarahkan_ke_halaman_terakhir_yang_berisi(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Category::factory()->create(['name' => sprintf('Kategori %02d', $i)]);
        }
        $terakhir = Category::where('name', 'Kategori 16')->sole();
        $halaman2 = $this->daftar(['status' => 'aktif', 'page' => 2]);

        $this->actingAs($this->admin)
            ->from($halaman2)
            ->delete(route('admin.categories.destroy', $terakhir))
            ->assertRedirect($halaman2);

        $this->actingAs($this->admin)
            ->get($halaman2)
            ->assertRedirect($this->daftar(['status' => 'aktif', 'page' => 1]));

        $this->actingAs($this->admin)
            ->get($this->daftar(['status' => 'aktif', 'page' => 1]))
            ->assertOk()
            ->assertSee('Kategori "Kategori 16" berhasil dihapus.')
            ->assertSee('Kategori 15');
    }

    public function test_nonaktifkan_data_terakhir_di_halaman_filter_aktif_juga_diarahkan(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Category::factory()->create(['name' => sprintf('Kategori %02d', $i)]);
        }
        $terakhir = Category::where('name', 'Kategori 16')->sole();
        $halaman2 = $this->daftar(['status' => 'aktif', 'page' => 2]);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($halaman2)
            ->patch(route('admin.categories.deactivate', $terakhir))
            ->assertOk()
            ->assertSee('Kategori "Kategori 16" dinonaktifkan.')
            ->assertSee('Kategori 15');
    }

    public function test_halaman_melebihi_jumlah_data_tanpa_data_tetap_di_halaman_1(): void
    {
        $this->actingAs($this->admin)
            ->get($this->daftar(['page' => 3]))
            ->assertRedirect($this->daftar(['page' => 1]));

        $this->actingAs($this->admin)
            ->get($this->daftar(['page' => 1]))
            ->assertOk()
            ->assertSee('Belum ada kategori.');
    }

    public function test_flash_sukses_dan_error_tampil_di_halaman(): void
    {
        $dipakai = Category::factory()->create(['name' => 'Elektronik']);
        $this->tambahListing(['category_id' => $dipakai->id, 'listing_type' => ListingType::Preloved->value]);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->from($this->daftar())
            ->delete(route('admin.categories.destroy', $dipakai))
            ->assertOk()
            ->assertSee('Kategori sudah dipakai listing; hanya dapat dinonaktifkan.')
            ->assertSee('dusk="alert"', false);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('admin.categories.store'), ['name' => 'Buku', 'listing_types' => ['PRELOVED']])
            ->assertOk()
            ->assertSee('Kategori "Buku" berhasil ditambahkan.');
    }

    public function test_keadaan_kosong_dengan_dan_tanpa_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Belum ada kategori.');

        Category::factory()->create(['name' => 'Elektronik']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada kategori yang cocok dengan pencarian atau filter.');
    }
}
