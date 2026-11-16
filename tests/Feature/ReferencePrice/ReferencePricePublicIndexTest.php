<?php

namespace Tests\Feature\ReferencePrice;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Halaman publik /harga-acuan: harga terbaru per pasangan material aktif + provinsi.
 */
class ReferencePricePublicIndexTest extends TestCase
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

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Penerbit Rahasia']);
        $this->plastik = Material::factory()->create(['name' => 'Plastik PET', 'slug' => 'plastik-pet']);
        $this->provinsiA = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function daftar(array $query = []): string
    {
        return route('reference-prices.index', $query);
    }

    private function harga(string $tanggal, int $hargaPerKg = 3000, ?Material $material = null, ?Region $provinsi = null, string $sumber = 'Survei harga pengepul'): ReferencePrice
    {
        return ReferencePrice::factory()
            ->forMaterial($material ?? $this->plastik)
            ->forRegion($provinsi ?? $this->provinsiA)
            ->publishedBy($this->admin)
            ->effectiveOn($tanggal)
            ->create(['price_per_kg' => $hargaPerKg, 'source' => $sumber]);
    }

    /**
     * Id entri yang tampil sebagai baris, sesuai urutan di tabel.
     *
     * @return list<int>
     */
    private function idBaris(string $html): array
    {
        preg_match_all('/dusk="reference-price-row-(\d+)"/', $html, $cocok);

        return array_map('intval', $cocok[1]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function ambilIdBaris(array $query = []): array
    {
        return $this->idBaris($this->get($this->daftar($query))->assertOk()->getContent());
    }

    private function jumlahQueryDaftar(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($this->daftar())->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    public function test_tamu_warga_dan_admin_bisa_membuka_daftar(): void
    {
        $harga = $this->harga('2026-11-01');

        $this->get($this->daftar())->assertOk()->assertSee('dusk="reference-price-row-'.$harga->id.'"', false);
        $this->actingAs(User::factory()->create())->get($this->daftar())->assertOk()->assertSee('Rp 3.000/kg');
        $this->actingAs($this->admin)->get($this->daftar())->assertOk()->assertSee('Rp 3.000/kg');
    }

    public function test_menampilkan_harga_terbaru_dengan_format_indonesia_dan_tautan_riwayat(): void
    {
        $this->harga('2026-10-01', 3200);
        $harga = $this->harga('2026-11-08', 3500, sumber: 'Rata-rata harga bank sampah');

        $this->get($this->daftar())
            ->assertOk()
            ->assertSee('<title>Harga Acuan Material', false)
            ->assertSeeInOrder(['Material', 'Provinsi', 'Harga/kg', 'Berlaku sejak', 'Sumber', 'Riwayat'])
            ->assertSeeInOrder(['Plastik PET', 'Provinsi Alfa', 'Rp 3.500/kg', '8 Nov 2026', 'Rata-rata harga bank sampah', 'Lihat riwayat'])
            ->assertDontSee('Rp 3.200/kg')
            ->assertSee('href="'.route('reference-prices.show', ['material' => 'plastik-pet', 'region' => 'PA']).'"', false)
            ->assertSee('harga Plastik PET di Provinsi Alfa')
            ->assertSee('role="region"', false)
            ->assertSee('aria-label="Tabel harga acuan terbaru"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('Harga acuan terbaru per material dan provinsi, halaman 1 dari 1')
            ->assertSee('role="search"', false)
            ->assertSee('<label for="material"', false)
            ->assertSee('<label for="provinsi"', false);

        $this->assertSame([$harga->id], $this->ambilIdBaris());
    }

    public function test_keterangan_harga_informatif_tampil(): void
    {
        $this->get($this->daftar())
            ->assertOk()
            ->assertSee('Harga acuan bersifat informatif dan tidak mengikat harga penjual.')
            ->assertSee('hanya berlaku untuk provinsi yang tercantum');
    }

    public function test_entri_terjadwal_tidak_tampil(): void
    {
        $berlaku = $this->harga('2026-11-01', 3000);
        $this->harga('2026-11-12', 3900);

        $kertas = Material::factory()->create(['name' => 'Kertas']);
        $this->harga('2026-11-20', 1800, $kertas);

        $this->get($this->daftar())
            ->assertOk()
            ->assertSee('Rp 3.000/kg')
            ->assertDontSee('Rp 3.900/kg')
            ->assertDontSee('Rp 1.800/kg')
            ->assertDontSee('<th scope="row" class="px-4 py-3 text-left font-medium text-gray-900">Kertas</th>', false);

        $this->assertSame([$berlaku->id], $this->ambilIdBaris());
    }

    public function test_tanggal_berlaku_sama_memakai_entri_id_terbesar(): void
    {
        $this->harga('2026-11-01', 3000);
        $koreksi = $this->harga('2026-11-01', 3100);

        $this->get($this->daftar())->assertOk()->assertSee('Rp 3.100/kg')->assertDontSee('Rp 3.000/kg');
        $this->assertSame([$koreksi->id], $this->ambilIdBaris());
    }

    public function test_batas_hari_mengikuti_wib(): void
    {
        $this->harga('2026-11-01', 3000);
        $besok = $this->harga('2026-11-12', 3600);

        $this->get($this->daftar())->assertOk()->assertDontSee('Rp 3.600/kg');

        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12.
        Carbon::setTestNow(Carbon::parse('2026-11-11 23:30:00', 'UTC'));

        $this->get($this->daftar())->assertOk()->assertSee('Rp 3.600/kg')->assertSee('12 Nov 2026')->assertDontSee('Rp 3.000/kg');
        $this->assertSame([$besok->id], $this->ambilIdBaris());
    }

    public function test_material_nonaktif_tidak_tampil_di_tabel_maupun_filter(): void
    {
        $lama = Material::factory()->inactive()->create(['name' => 'Material Lama']);
        $this->harga('2026-10-01', 2000, $lama);
        $aktif = $this->harga('2026-10-01', 3000);

        $this->get($this->daftar())
            ->assertOk()
            ->assertDontSee('Material Lama')
            ->assertDontSee('Rp 2.000/kg');

        $this->assertSame([$aktif->id], $this->ambilIdBaris());
    }

    public function test_hanya_provinsi_yang_tampil(): void
    {
        $kota = Region::create(['name' => 'Kota Contoh', 'code' => 'PA.01', 'parent_id' => $this->provinsiA->id]);
        $this->harga('2026-10-01', 2000, provinsi: $kota);
        $provinsi = $this->harga('2026-10-01', 3000);

        $this->get($this->daftar())
            ->assertOk()
            ->assertDontSee('Kota Contoh')
            ->assertDontSee('Rp 2.000/kg');

        $this->assertSame([$provinsi->id], $this->ambilIdBaris());
    }

    public function test_nama_penerbit_tidak_tampil(): void
    {
        $this->harga('2026-11-01');

        $this->get($this->daftar())->assertOk()->assertDontSee('Admin Penerbit Rahasia')->assertDontSee('Penerbit');
    }

    public function test_urut_nama_material_lalu_nama_provinsi(): void
    {
        $kertas = Material::factory()->create(['name' => 'Kertas']);
        $aluminium = Material::factory()->create(['name' => 'Aluminium']);
        $provinsiB = Region::create(['name' => 'Bengkulu Contoh', 'code' => 'PB']);

        $plastikA = $this->harga('2026-11-01', 3000);
        $kertasA = $this->harga('2026-11-01', 1500, $kertas);
        $aluminiumA = $this->harga('2026-11-01', 9000, $aluminium);
        $plastikB = $this->harga('2026-11-01', 3100, provinsi: $provinsiB);
        $aluminiumB = $this->harga('2026-11-01', 9100, $aluminium, $provinsiB);

        $this->assertSame([$aluminiumB->id, $aluminiumA->id, $kertasA->id, $plastikB->id, $plastikA->id], $this->ambilIdBaris());
    }

    public function test_filter_material_dan_provinsi(): void
    {
        $kertas = Material::factory()->create(['name' => 'Kertas']);
        $provinsiB = Region::create(['name' => 'Provinsi Beta', 'code' => 'PB']);
        $plastikA = $this->harga('2026-11-01', 3000);
        $plastikB = $this->harga('2026-11-01', 3100, provinsi: $provinsiB);
        $kertasA = $this->harga('2026-11-01', 1500, $kertas);

        $this->assertEqualsCanonicalizing([$plastikA->id, $plastikB->id], $this->ambilIdBaris(['material' => $this->plastik->id]));
        $this->assertEqualsCanonicalizing([$plastikB->id], $this->ambilIdBaris(['provinsi' => $provinsiB->id]));
        $this->assertSame([$kertasA->id], $this->ambilIdBaris(['material' => $kertas->id, 'provinsi' => $this->provinsiA->id]));

        $this->get($this->daftar(['material' => $kertas->id, 'provinsi' => $provinsiB->id]))
            ->assertOk()
            ->assertSee('<option value="'.$kertas->id.'" selected', false)
            ->assertSee('<option value="'.$provinsiB->id.'" selected', false)
            ->assertSee('dusk="reference-price-filter-reset"', false)
            ->assertSee('Tidak ada harga acuan yang cocok dengan filter.')
            ->assertSee('dusk="reference-price-empty-reset"', false);
    }

    public function test_pilihan_filter_hanya_material_aktif_dan_provinsi_urut_nama(): void
    {
        Material::factory()->create(['name' => 'Kertas']);
        Material::factory()->inactive()->create(['name' => 'Material Lama']);
        Region::create(['name' => 'Aceh Contoh', 'code' => 'P0']);
        Region::create(['name' => 'Kota Contoh', 'code' => 'PA.01', 'parent_id' => $this->provinsiA->id]);

        $this->get($this->daftar())
            ->assertOk()
            ->assertSeeInOrder(['Semua material', 'Kertas', 'Plastik PET'])
            ->assertSeeInOrder(['Semua provinsi', 'Aceh Contoh', 'Provinsi Alfa'])
            ->assertDontSee('Material Lama')
            ->assertDontSee('Kota Contoh');
    }

    public function test_filter_yang_tidak_valid_diabaikan(): void
    {
        $lama = Material::factory()->inactive()->create(['name' => 'Material Lama']);
        $kota = Region::create(['name' => 'Kota Contoh', 'code' => 'PA.01', 'parent_id' => $this->provinsiA->id]);
        $harga = $this->harga('2026-11-01');

        $percobaan = [
            ['material' => 'plastik', 'provinsi' => 'semua'],
            ['material' => ['1'], 'provinsi' => ['1']],
            ['material' => '-1', 'provinsi' => '0'],
            ['material' => '99999', 'provinsi' => '99999'],
            ['material' => (string) $lama->id, 'provinsi' => (string) $kota->id],
        ];

        foreach ($percobaan as $query) {
            $this->get($this->daftar($query))
                ->assertOk()
                ->assertSee('dusk="reference-price-row-'.$harga->id.'"', false)
                ->assertDontSee('dusk="reference-price-filter-reset"', false)
                ->assertDontSee('" selected', false);
        }
    }

    public function test_paginasi_15_per_halaman_dengan_query_string_tetap(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->harga('2026-11-01', 1000 + $i, provinsi: Region::create(['name' => sprintf('Provinsi %02d', $i), 'code' => 'P'.$i]));
        }

        $filter = ['material' => $this->plastik->id];

        $this->get($this->daftar($filter))
            ->assertOk()
            ->assertSee('Rp 1.001/kg')
            ->assertSee('Rp 1.015/kg')
            ->assertDontSee('Rp 1.016/kg')
            ->assertSee(e($this->daftar([...$filter, 'page' => 2])), false);

        $this->get($this->daftar([...$filter, 'page' => 2]))
            ->assertOk()
            ->assertSee('Rp 1.016/kg')
            ->assertSee('Rp 1.017/kg')
            ->assertDontSee('Rp 1.015/kg')
            ->assertSee('Harga acuan terbaru per material dan provinsi, halaman 2 dari 2');
    }

    public function test_halaman_melebihi_jumlah_data_dialihkan_ke_halaman_terakhir(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->harga('2026-11-01', 1000 + $i, provinsi: Region::create(['name' => sprintf('Provinsi %02d', $i), 'code' => 'P'.$i]));
        }

        $this->get($this->daftar(['material' => $this->plastik->id, 'page' => 5]))
            ->assertRedirect($this->daftar(['material' => $this->plastik->id, 'page' => 2]));

        $this->get($this->daftar(['page' => 'abc']))->assertOk();
    }

    public function test_keadaan_kosong_dibedakan_tanpa_dan_dengan_filter(): void
    {
        $this->get($this->daftar(['page' => 3]))
            ->assertRedirect($this->daftar(['page' => 1]));

        $this->get($this->daftar())
            ->assertOk()
            ->assertSee('Belum ada harga acuan yang berlaku.')
            ->assertDontSee('Tidak ada harga acuan yang cocok dengan filter.')
            ->assertDontSee('dusk="reference-price-empty-reset"', false)
            ->assertDontSee('dusk="reference-price-filter-reset"', false);

        // Hanya entri terjadwal: belum ada yang berlaku.
        $this->harga('2026-11-20');

        $this->get($this->daftar())->assertOk()->assertSee('Belum ada harga acuan yang berlaku.');

        $this->get($this->daftar(['material' => $this->plastik->id]))
            ->assertOk()
            ->assertSee('Tidak ada harga acuan yang cocok dengan filter.')
            ->assertDontSee('Belum ada harga acuan yang berlaku.')
            ->assertSee('href="'.$this->daftar().'"', false)
            ->assertSee('Tampilkan semua harga acuan');
    }

    public function test_jumlah_query_sama_untuk_2_dan_20_pasangan(): void
    {
        $this->harga('2026-10-01', 3000);
        $this->harga('2026-11-01', 3100);
        $this->harga('2026-11-01', 1500, Material::factory()->create());

        $this->jumlahQueryDaftar();
        $denganDuaPasangan = $this->jumlahQueryDaftar();

        // 18 pasangan lagi dengan material, provinsi, dan penerbit yang berbeda-beda (riwayat 2 entri).
        for ($i = 0; $i < 18; $i++) {
            $material = Material::factory()->create();
            $provinsi = Region::create(['name' => 'Provinsi Uji '.$i, 'code' => 'Q'.$i]);
            ReferencePrice::factory()->forMaterial($material)->forRegion($provinsi)->effectiveOn('2026-09-01')->create();
            ReferencePrice::factory()->forMaterial($material)->forRegion($provinsi)->effectiveOn('2026-10-01')->create();
        }

        $this->assertSame(20, ReferencePrice::latestPerPair('2026-11-11')->count());
        $this->assertSame($denganDuaPasangan, $this->jumlahQueryDaftar(), 'Jumlah query halaman publik harus sama untuk 2 dan 20 pasangan.');
    }

    public function test_tautan_riwayat_membawa_filter_dan_halaman_saat_ini(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->harga('2026-11-01', 1000 + $i, provinsi: Region::create(['name' => sprintf('Provinsi %02d', $i), 'code' => 'P'.$i]));
        }

        $html = $this->get($this->daftar(['material' => $this->plastik->id, 'page' => 2]))->assertOk()->getContent();

        $detail = route('reference-prices.show', [
            'material' => 'plastik-pet',
            'region' => 'P16',
            'kembali' => http_build_query(['material' => $this->plastik->id, 'page' => 2]),
        ]);
        $this->assertStringContainsString('href="'.e($detail).'"', $html);

        // Tanpa filter di halaman pertama: tautan tanpa parameter kembali.
        $this->get($this->daftar())
            ->assertOk()
            ->assertSee('href="'.route('reference-prices.show', ['material' => 'plastik-pet', 'region' => 'P1']).'"', false)
            ->assertDontSee('kembali=', false);
    }

    public function test_tidak_ada_form_pengubah_data_di_halaman_publik(): void
    {
        $this->harga('2026-11-01');

        $html = $this->actingAs($this->admin)->get($this->daftar())->assertOk()->getContent();

        // Hanya isi halaman; form logout di navbar memang memakai POST.
        $this->assertSame(1, preg_match('/<main\b.*<\/main>/s', $html, $main));
        $this->assertStringNotContainsString('method="POST"', $main[0]);
        $this->assertStringNotContainsString('name="_method"', $main[0]);
        $this->assertStringNotContainsString('/admin/harga-acuan', $main[0]);
    }
}
