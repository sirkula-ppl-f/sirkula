<?php

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferencePriceIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Material $plastik;

    private Region $provinsiA;

    protected function setUp(): void
    {
        parent::setUp();

        // 12.00 WIB, 11 November 2026.
        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Penerbit']);
        $this->plastik = Material::factory()->create(['name' => 'Plastik PET']);
        $this->provinsiA = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function daftar(array $query = []): string
    {
        return route('admin.reference-prices.index', $query);
    }

    private function harga(string $tanggal, int $hargaPerKg = 3000, ?Material $material = null, ?Region $provinsi = null): ReferencePrice
    {
        return ReferencePrice::factory()
            ->forMaterial($material ?? $this->plastik)
            ->forRegion($provinsi ?? $this->provinsiA)
            ->publishedBy($this->admin)
            ->effectiveOn($tanggal)
            ->create(['price_per_kg' => $hargaPerKg]);
    }

    /**
     * Isi baris tabel untuk entri tertentu.
     */
    private function baris(string $html, ReferencePrice $harga): string
    {
        $pola = '/<tr dusk="reference-price-row-'.$harga->id.'">(.*?)<\/tr>/s';
        $this->assertSame(1, preg_match($pola, $html, $cocok), "Baris harga {$harga->id} tidak ditemukan.");

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

    public function test_menampilkan_kolom_riwayat_dengan_format_indonesia(): void
    {
        ReferencePrice::factory()->forMaterial($this->plastik)->forRegion($this->provinsiA)->publishedBy($this->admin)
            ->effectiveOn('2026-11-01')
            ->create(['price_per_kg' => 3500, 'source' => 'Survei harga pengepul']);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Kelola Harga Acuan')
            ->assertSeeInOrder(['Material', 'Provinsi', 'Harga/kg', 'Tanggal berlaku', 'Status', 'Sumber', 'Penerbit', 'Waktu terbit'])
            ->assertSeeInOrder([
                'Plastik PET',
                'Provinsi Alfa',
                'Rp 3.500/kg',
                '1 Nov 2026',
                'Berlaku',
                'Survei harga pengepul',
                'Admin Penerbit',
                '11 Nov 2026, 12.00 WIB',
            ])
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="Tabel riwayat harga acuan"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('Riwayat harga acuan, terbaru di atas, halaman 1 dari 1')
            ->assertSee('href="'.route('admin.reference-prices.create').'"', false);
    }

    public function test_label_berlaku_terjadwal_dan_riwayat_per_pasangan(): void
    {
        $kertas = Material::factory()->create(['name' => 'Kertas']);
        $lama = $this->harga('2026-10-01');
        $koreksiLama = $this->harga('2026-11-01', 3100);
        $berlaku = $this->harga('2026-11-01', 3200);
        $terjadwal = $this->harga('2026-11-12', 3300);
        $kertasBerlaku = $this->harga('2025-06-01', 1500, $kertas);

        $html = $this->actingAs($this->admin)->get($this->daftar())->assertOk()->getContent();

        $this->assertStringContainsString('Riwayat', $this->baris($html, $lama));
        $this->assertStringContainsString('Riwayat', $this->baris($html, $koreksiLama));
        $this->assertStringContainsString('Berlaku', $this->baris($html, $berlaku));
        $this->assertStringContainsString('Terjadwal', $this->baris($html, $terjadwal));
        $this->assertStringContainsString('Berlaku', $this->baris($html, $kertasBerlaku));

        // Terbaru di atas: tanggal berlaku terbaru, lalu id terbesar.
        $this->assertSame(
            [$terjadwal->id, $berlaku->id, $koreksiLama->id, $lama->id, $kertasBerlaku->id],
            array_map('intval', preg_match_all('/dusk="reference-price-row-(\d+)"/', $html, $cocok) ? $cocok[1] : [])
        );
    }

    public function test_label_terjadwal_berubah_menjadi_berlaku_saat_tanggalnya_tiba_di_wib(): void
    {
        $sekarang = $this->harga('2026-11-01');
        $besok = $this->harga('2026-11-12', 3600);

        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12.
        $this->travelTo(Carbon::parse('2026-11-11 23:30:00', 'UTC'));

        $html = $this->actingAs($this->admin)->get($this->daftar())->assertOk()->getContent();

        $this->assertStringContainsString('Berlaku', $this->baris($html, $besok));
        $this->assertStringContainsString('Riwayat', $this->baris($html, $sekarang));
    }

    public function test_material_nonaktif_tetap_tampil_dengan_tanda_nonaktif(): void
    {
        $lama = Material::factory()->inactive()->create(['name' => 'Material Lama']);
        $harga = $this->harga('2026-10-01', 2000, $lama);

        $html = $this->actingAs($this->admin)->get($this->daftar())->assertOk()->getContent();

        $baris = $this->baris($html, $harga);
        $this->assertStringContainsString('Material Lama', $baris);
        $this->assertStringContainsString('Nonaktif', $baris);
        $this->assertStringContainsString('Material Lama (nonaktif)', $html);
    }

    public function test_tidak_ada_aksi_ubah_atau_hapus_dan_ada_keterangan_append_only(): void
    {
        $this->harga('2026-11-01');

        $html = $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Riwayat harga tidak dapat diubah atau dihapus. Untuk mengoreksi salah input, terbitkan entri baru')
            ->getContent();

        // Hanya isi halaman; form logout di navbar memang memakai POST.
        $this->assertSame(1, preg_match('/<main\b.*<\/main>/s', $html, $main));
        $this->assertStringNotContainsString('method="POST"', $main[0]);
        $this->assertStringNotContainsString('name="_method"', $main[0]);
        $this->assertStringNotContainsString('data-confirm=', $main[0]);
        $this->assertStringNotContainsString('/ubah', $main[0]);
    }

    public function test_filter_material_provinsi_dan_rentang_tanggal(): void
    {
        $kertas = Material::factory()->create(['name' => 'Kertas']);
        $provinsiB = Region::create(['name' => 'Provinsi Beta', 'code' => 'PB']);
        $plastikA = $this->harga('2026-09-15', 3000);
        $plastikB = $this->harga('2026-10-15', 3100, provinsi: $provinsiB);
        $kertasA = $this->harga('2026-10-20', 1500, $kertas);
        $plastikAJuni = $this->harga('2026-06-01', 2500);

        $ambil = function (array $query): array {
            $html = $this->actingAs($this->admin)->get($this->daftar($query))->assertOk()->getContent();
            preg_match_all('/dusk="reference-price-row-(\d+)"/', $html, $cocok);

            return array_map('intval', $cocok[1]);
        };

        $this->assertEqualsCanonicalizing([$plastikA->id, $plastikB->id, $plastikAJuni->id], $ambil(['material' => $this->plastik->id]));
        $this->assertEqualsCanonicalizing([$plastikB->id], $ambil(['provinsi' => $provinsiB->id]));
        $this->assertEqualsCanonicalizing([$plastikA->id, $plastikB->id], $ambil(['dari' => '2026-09-15', 'sampai' => '2026-10-15']));
        $this->assertEqualsCanonicalizing([$kertasA->id, $plastikB->id], $ambil(['dari' => '2026-10-01']));
        $this->assertEqualsCanonicalizing([$plastikAJuni->id], $ambil(['sampai' => '2026-06-01']));
        $this->assertEqualsCanonicalizing([$plastikA->id], $ambil(['material' => $this->plastik->id, 'provinsi' => $this->provinsiA->id, 'dari' => '2026-07-01']));

        $this->actingAs($this->admin)
            ->get($this->daftar(['material' => $kertas->id, 'provinsi' => $provinsiB->id]))
            ->assertOk()
            ->assertSee('Tidak ada harga acuan yang cocok dengan filter.')
            ->assertSee('<option value="'.$kertas->id.'" selected', false)
            ->assertSee('<option value="'.$provinsiB->id.'" selected', false)
            ->assertSee('dusk="reference-price-filter-reset"', false);
    }

    public function test_filter_hanya_menawarkan_provinsi(): void
    {
        Region::create(['name' => 'Kota Contoh', 'code' => 'PA.01', 'parent_id' => $this->provinsiA->id]);

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Provinsi Alfa')
            ->assertDontSee('Kota Contoh');
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        $harga = $this->harga('2026-11-01');

        $this->actingAs($this->admin)
            ->get($this->daftar(['material' => 'plastik', 'provinsi' => ['1'], 'dari' => '2026-13-45', 'sampai' => 'kemarin']))
            ->assertOk()
            ->assertSee('dusk="reference-price-row-'.$harga->id.'"', false)
            ->assertDontSee('dusk="reference-price-filter-reset"', false);
    }

    public function test_paginasi_15_per_halaman_dengan_query_string_tetap(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->harga(sprintf('2026-10-%02d', $i), 1000 + $i);
        }

        $filter = ['material' => $this->plastik->id];

        $this->actingAs($this->admin)
            ->get($this->daftar($filter))
            ->assertOk()
            ->assertSee('Rp 1.017/kg')
            ->assertSee('Rp 1.003/kg')
            ->assertDontSee('Rp 1.002/kg')
            ->assertSee(e($this->daftar([...$filter, 'page' => 2])), false);

        $this->actingAs($this->admin)
            ->get($this->daftar([...$filter, 'page' => 2]))
            ->assertOk()
            ->assertSee('Rp 1.002/kg')
            ->assertSee('Rp 1.001/kg')
            ->assertDontSee('Rp 1.003/kg')
            ->assertSee('Riwayat harga acuan, terbaru di atas, halaman 2 dari 2');
    }

    public function test_halaman_melebihi_jumlah_data_dialihkan_ke_halaman_terakhir(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->harga(sprintf('2026-10-%02d', $i));
        }

        $this->actingAs($this->admin)
            ->get($this->daftar(['material' => $this->plastik->id, 'page' => 5]))
            ->assertRedirect($this->daftar(['material' => $this->plastik->id, 'page' => 2]));
    }

    public function test_keadaan_kosong_dengan_dan_tanpa_filter(): void
    {
        $this->actingAs($this->admin)
            ->get($this->daftar(['page' => 3]))
            ->assertRedirect($this->daftar(['page' => 1]));

        $this->actingAs($this->admin)
            ->get($this->daftar())
            ->assertOk()
            ->assertSee('Belum ada harga acuan yang diterbitkan.')
            ->assertDontSee('dusk="reference-price-filter-reset"', false);

        $this->actingAs($this->admin)
            ->get($this->daftar(['dari' => '2026-01-01']))
            ->assertOk()
            ->assertSee('Tidak ada harga acuan yang cocok dengan filter.');
    }

    public function test_flash_terbit_tampil_di_halaman_riwayat(): void
    {
        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('admin.reference-prices.store'), [
                'material_id' => $this->plastik->id,
                'region_id' => $this->provinsiA->id,
                'price_per_kg' => 4200,
                'effective_date' => '2026-11-11',
                'source' => 'Survei harga pengepul',
            ])
            ->assertOk()
            ->assertSee('Harga acuan Plastik PET di Provinsi Alfa sebesar Rp 4.200/kg berhasil diterbitkan, berlaku sejak 11 Nov 2026.')
            ->assertSee('Rp 4.200/kg');
    }

    public function test_jumlah_query_tidak_bertambah_per_baris(): void
    {
        $this->harga('2026-10-01');
        $this->harga('2026-11-01');

        $this->jumlahQueryDaftar();
        $denganDuaBaris = $this->jumlahQueryDaftar();

        // 13 entri lagi dengan material, provinsi, dan penerbit yang berbeda-beda.
        ReferencePrice::factory()->count(13)->create();

        $this->assertSame($denganDuaBaris, $this->jumlahQueryDaftar(), 'Jumlah query halaman riwayat harus sama untuk 2 dan 15 baris.');
    }
}
