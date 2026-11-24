<?php

namespace Tests\Feature\Admin;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PBI-06: daftar listing admin dengan pencarian, filter, dan paginasi.
 */
class ListingModerationIndexTest extends TestCase
{
    use RefreshDatabase;

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
        return route('admin.listings.index', $query);
    }

    /**
     * ID listing yang tampil di tabel, urut sesuai halaman.
     *
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function ambil(array $query = []): array
    {
        $html = $this->actingAs($this->admin)->get($this->daftar($query))->assertOk()->getContent();
        preg_match_all('/dusk="listing-row-(\d+)"/', $html, $cocok);

        return array_map('intval', $cocok[1]);
    }

    private function hub(string $nama, Region $region, string $status = 'ACTIVE'): Hub
    {
        return Hub::create([
            'name' => $nama,
            'type' => 'KOMUNITAS',
            'region_id' => $region->id,
            'public_address' => 'Jl. Contoh No. 1',
            'status' => $status,
        ]);
    }

    /**
     * Isi sel "Tampil di katalog" untuk listing ini.
     */
    private function tampilDiKatalog(string $html, Listing $listing): string
    {
        $this->assertSame(1, preg_match('/dusk="listing-catalog-'.$listing->id.'">\s*([^<]+?)\s*</', $html, $cocok));

        return $cocok[1];
    }

    private function jumlahQueryDaftar(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get($this->daftar())->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    public function test_menampilkan_kolom_listing_dengan_format_indonesia(): void
    {
        $provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        $listing = Listing::factory()->create([
            'title' => 'Rak Buku Kayu',
            'price' => 275000,
            'listing_type' => ListingType::Preloved->value,
            'category_id' => Category::factory()->create(['name' => 'Perabot Rumah'])->id,
            'user_id' => User::factory()->create(['name' => 'Penjual Satu'])->id,
            'hub_id' => $this->hub('Hub Alfa', $provinsi)->id,
            'region_id' => $provinsi->id,
        ]);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Moderasi Listing')
            ->assertSeeInOrder(['Listing', 'Tipe', 'Kategori', 'Penjual', 'Hub', 'Wilayah', 'Harga', 'Status verifikasi', 'Status moderasi', 'Tampil di katalog', 'Aksi'])
            ->assertSeeInOrder(['Rak Buku Kayu', 'ID '.$listing->id, 'Barang Bekas', 'Perabot Rumah', 'Penjual Satu', 'Hub Alfa', 'Provinsi Alfa', 'Rp 275.000', 'Disetujui hub', 'Diizinkan tampil', 'Ya', 'Detail'])
            ->assertSee('href="'.route('admin.listings.show', $listing).'"', false)
            ->assertSee('listing Rak Buku Kayu')
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="Tabel listing"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('Daftar listing, terbaru di atas, halaman 1 dari 1')
            ->assertSee('href="'.route('admin.listings.moderations').'"', false);
    }

    public function test_listing_draf_tidak_tampil_dan_urut_terbaru(): void
    {
        $draf = Listing::factory()->create(['review_status' => Listing::REVIEW_DRAFT, 'publish_status' => 'INACTIVE']);
        $pertama = Listing::factory()->create();
        $kedua = Listing::factory()->create(['review_status' => Listing::REVIEW_PENDING]);
        $ketiga = Listing::factory()->dinonaktifkanAdmin()->create();

        $ids = $this->ambil();

        $this->assertSame([$ketiga->id, $kedua->id, $pertama->id], $ids);
        $this->assertNotContains($draf->id, $ids);
    }

    public function test_kolom_tampil_di_katalog_dan_status_verifikasi_yang_tidak_dikenal(): void
    {
        $provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        $tampil = Listing::factory()->create();
        $disembunyikan = Listing::factory()->dinonaktifkanAdmin()->create();
        $menunggu = Listing::factory()->create(['review_status' => Listing::REVIEW_PENDING]);
        $stokHabis = Listing::factory()->stok(0)->create();
        $hubArsip = Listing::factory()->create(['hub_id' => $this->hub('Hub Lama', $provinsi, 'ARCHIVED')->id]);
        $nilaiFactory = Listing::factory()->belumDisetujui()->create();

        $html = $this->actingAs($this->admin)->get($this->daftar())->assertOk()->getContent();

        $this->assertSame('Ya', $this->tampilDiKatalog($html, $tampil));

        foreach ([$disembunyikan, $menunggu, $stokHabis, $hubArsip, $nilaiFactory] as $listing) {
            $this->assertSame('Tidak', $this->tampilDiKatalog($html, $listing), "Listing {$listing->id}");
        }

        $this->assertStringContainsString('Menunggu verifikasi hub', $html);
        $this->assertStringContainsString('Dinonaktifkan admin', $html);
        // Nilai yang belum punya label ditampilkan apa adanya, tanpa error.
        $this->assertMatchesRegularExpression('/<td[^>]*>PENDING<\/td>/', $html);
    }

    public function test_filter_tipe_kategori_hub_provinsi_verifikasi_dan_moderasi(): void
    {
        $provinsiA = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        $kotaA = Region::create(['name' => 'Kota Alfa', 'code' => 'PA.01', 'parent_id' => $provinsiA->id]);
        $provinsiB = Region::create(['name' => 'Provinsi Beta', 'code' => 'PB']);
        $hubA = $this->hub('Hub Alfa', $provinsiA);
        $hubB = $this->hub('Hub Beta', $provinsiB);
        $kategoriA = Category::factory()->create(['name' => 'Kategori Alfa']);
        $kategoriB = Category::factory()->create(['name' => 'Kategori Beta']);

        $preloved = Listing::factory()->create([
            'listing_type' => ListingType::Preloved->value, 'category_id' => $kategoriA->id,
            'hub_id' => $hubA->id, 'region_id' => $provinsiA->id,
        ]);
        $recyclable = Listing::factory()->create([
            'listing_type' => ListingType::Recyclable->value, 'category_id' => $kategoriB->id,
            'region_id' => $kotaA->id, 'review_status' => Listing::REVIEW_PENDING,
        ]);
        $upcycle = Listing::factory()->dinonaktifkanAdmin()->create([
            'listing_type' => ListingType::Upcycle->value, 'category_id' => $kategoriA->id,
            'hub_id' => $hubB->id, 'region_id' => $provinsiB->id,
        ]);

        $this->assertSame([$recyclable->id], $this->ambil(['tipe' => 'RECYCLABLE']));
        $this->assertSame([$upcycle->id, $preloved->id], $this->ambil(['kategori' => $kategoriA->id]));
        $this->assertSame([$preloved->id], $this->ambil(['hub' => $hubA->id]));
        // Filter provinsi ikut mencakup wilayah di bawahnya.
        $this->assertSame([$recyclable->id, $preloved->id], $this->ambil(['provinsi' => $provinsiA->id]));
        $this->assertSame([$recyclable->id], $this->ambil(['verifikasi' => Listing::REVIEW_PENDING]));
        $this->assertSame([$upcycle->id], $this->ambil(['moderasi' => 'HIDDEN']));
        $this->assertSame([$recyclable->id, $preloved->id], $this->ambil(['moderasi' => 'VISIBLE']));
        $this->assertSame([], $this->ambil(['tipe' => 'PRELOVED', 'moderasi' => 'HIDDEN']));

        $this->actingAs($this->admin)
            ->get($this->daftar(['tipe' => 'PRELOVED', 'moderasi' => 'HIDDEN', 'provinsi' => $provinsiA->id]))
            ->assertOk()
            ->assertSee('Tidak ada listing yang cocok dengan filter.')
            ->assertSee('dusk="listing-empty-reset"', false)
            ->assertSee('dusk="listing-filter-reset"', false)
            ->assertSee('<option value="PRELOVED" selected', false)
            ->assertSee('<option value="HIDDEN" selected', false)
            ->assertSee('<option value="'.$provinsiA->id.'" selected', false)
            // Hanya provinsi yang ditawarkan di filter (id kota bisa sama dengan id kategori/hub, jadi dicek lewat nama).
            ->assertSee('>Provinsi Beta</option>', false)
            ->assertDontSee('>Kota Alfa</option>', false);
    }

    public function test_kata_kunci_mencari_judul_dengan_escape_dan_angka_mencocokkan_id(): void
    {
        $diskon = Listing::factory()->create(['title' => 'Diskon 100% Botol Kaca']);
        $botol = Listing::factory()->create(['title' => 'Botol 100 ml Bekas']);
        $garisBawah = Listing::factory()->create(['title' => 'Kaos_Polos Ukuran L']);
        $spasi = Listing::factory()->create(['title' => 'Kaos Polos Ukuran M']);

        $this->assertSame([$diskon->id], $this->ambil(['cari' => '100%']));
        $this->assertSame([$garisBawah->id], $this->ambil(['cari' => 'Kaos_']));
        $this->assertSame([$spasi->id, $garisBawah->id], $this->ambil(['cari' => 'kaos']));
        $this->assertSame([$botol->id], $this->ambil(['cari' => '  100 ml  ']));
        $this->assertContains($botol->id, $this->ambil(['cari' => (string) $botol->id]));
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        $listing = Listing::factory()->create();

        $ids = $this->ambil([
            'tipe' => 'MOBIL',
            'kategori' => 'abc',
            'hub' => ['1'],
            'provinsi' => '-1',
            'verifikasi' => Listing::REVIEW_DRAFT,
            'moderasi' => 'hilang',
            'cari' => ['x'],
        ]);

        $this->assertSame([$listing->id], $ids);
        $this->actingAs($this->admin)
            ->get($this->daftar(['tipe' => 'MOBIL', 'verifikasi' => 'DRAFT']))
            ->assertDontSee('dusk="listing-filter-reset"', false);
    }

    public function test_paginasi_15_per_halaman_membawa_query_string(): void
    {
        $listings = Listing::factory()->count(17)->create(['listing_type' => ListingType::Preloved->value]);
        $filter = ['tipe' => 'PRELOVED'];

        $halamanSatu = $this->ambil($filter);
        $this->assertCount(15, $halamanSatu);
        $this->assertSame($listings->last()->id, $halamanSatu[0]);

        $this->actingAs($this->admin)
            ->get($this->daftar($filter))
            ->assertSee(e($this->daftar([...$filter, 'page' => 2])), false);

        $this->assertSame([$listings[1]->id, $listings[0]->id], $this->ambil([...$filter, 'page' => 2]));
        $this->actingAs($this->admin)
            ->get($this->daftar([...$filter, 'page' => 2]))
            ->assertSee('Daftar listing, terbaru di atas, halaman 2 dari 2');
    }

    public function test_halaman_melebihi_jumlah_data_dialihkan_ke_halaman_terakhir(): void
    {
        Listing::factory()->count(16)->create();

        $this->actingAs($this->admin)
            ->get($this->daftar(['moderasi' => 'VISIBLE', 'page' => 5]))
            ->assertRedirect($this->daftar(['moderasi' => 'VISIBLE', 'page' => 2]));
    }

    public function test_keadaan_kosong_dibedakan_antara_belum_ada_listing_dan_tidak_ada_hasil_filter(): void
    {
        Listing::factory()->create(['review_status' => Listing::REVIEW_DRAFT]);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Belum ada listing yang diajukan.')
            ->assertDontSee('dusk="listing-empty-reset"', false);

        Listing::factory()->create(['title' => 'Kursi Rotan']);

        $this->actingAs($this->admin)
            ->get($this->daftar(['cari' => 'Lemari']))
            ->assertOk()
            ->assertSee('Tidak ada listing yang cocok dengan filter.')
            ->assertSee('Tampilkan semua listing')
            ->assertSee('dusk="listing-empty-reset"', false);
    }

    public function test_jumlah_query_sama_untuk_2_dan_20_listing(): void
    {
        $provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        $buat = fn (int $jumlah) => Listing::factory()->count($jumlah)->create([
            'hub_id' => fn () => $this->hub('Hub '.fake()->unique()->numerify('###'), $provinsi)->id,
            'region_id' => $provinsi->id,
        ]);

        $buat(2);
        $this->jumlahQueryDaftar();
        $denganDua = $this->jumlahQueryDaftar();

        // 18 listing lagi dengan penjual, kategori, dan hub yang berbeda-beda.
        $buat(18);

        $this->assertSame($denganDua, $this->jumlahQueryDaftar(), 'Jumlah query daftar listing harus sama untuk 2 dan 20 listing.');
    }
}
