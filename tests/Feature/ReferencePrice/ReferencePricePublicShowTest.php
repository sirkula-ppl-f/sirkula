<?php

namespace Tests\Feature\ReferencePrice;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Halaman publik /harga-acuan/{material:slug}/{region:code}: harga terbaru, grafik tren, dan riwayat.
 */
class ReferencePricePublicShowTest extends TestCase
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
    private function detail(?Material $material = null, ?Region $provinsi = null, array $query = []): string
    {
        return route('reference-prices.show', [
            'material' => $material ?? $this->plastik,
            'region' => $provinsi ?? $this->provinsiA,
            ...$query,
        ]);
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
     * Isi elemen dengan atribut dusk tertentu.
     */
    private function bagian(string $html, string $dusk, string $tag = 'section'): string
    {
        $pola = '/<'.$tag.'\b[^>]*dusk="'.preg_quote($dusk, '/').'"[^>]*>(.*?)<\/'.$tag.'>/s';
        $this->assertSame(1, preg_match($pola, $html, $cocok), "Elemen {$dusk} tidak ditemukan.");

        return $cocok[1];
    }

    private function barisRiwayat(string $html, ReferencePrice $harga): string
    {
        return $this->bagian($html, 'reference-price-history-row-'.$harga->id, 'tr');
    }

    /**
     * @return list<int>
     */
    private function idRiwayat(string $html): array
    {
        preg_match_all('/dusk="reference-price-history-row-(\d+)"/', $html, $cocok);

        return array_map('intval', $cocok[1]);
    }

    private function grafik(string $html): string
    {
        $this->assertSame(1, preg_match('/<svg\b[^>]*dusk="trend-chart"[^>]*>.*?<\/svg>/s', $html, $cocok), 'Grafik tren tidak ditemukan.');

        return $cocok[0];
    }

    /**
     * Isi <title> tiap titik grafik, urut dari kiri.
     *
     * @return list<string>
     */
    private function titikGrafik(string $html): array
    {
        preg_match_all('/<circle\b[^>]*data-point[^>]*><title>(.*?)<\/title><\/circle>/s', $this->grafik($html), $cocok);

        return array_map(fn (string $judul) => trim(html_entity_decode($judul)), $cocok[1]);
    }

    public function test_url_sesuai_kontrak(): void
    {
        $this->assertSame(url('/harga-acuan'), route('reference-prices.index'));
        $this->assertSame(url('/harga-acuan/plastik-pet/PA'), $this->detail());
    }

    public function test_pasangan_valid_menampilkan_harga_terbaru_grafik_dan_riwayat(): void
    {
        $lama = $this->harga('2026-08-01', 3200);
        $berlaku = $this->harga('2026-11-08', 3500, sumber: 'Rata-rata harga bank sampah');

        $html = $this->get($this->detail())
            ->assertOk()
            ->assertSee('<title>Plastik PET di Provinsi Alfa', false)
            ->assertSee('Plastik PET di Provinsi Alfa')
            ->assertSee('href="'.route('reference-prices.index').'"', false)
            ->assertSee('Harga acuan bersifat informatif dan tidak mengikat harga penjual.')
            ->assertSeeInOrder(['Harga terbaru', 'Tren harga', 'Riwayat harga'])
            ->assertSee('aria-label="Tabel riwayat harga"', false)
            ->assertSee('Riwayat harga Plastik PET di Provinsi Alfa, terbaru di atas, halaman 1 dari 1')
            ->getContent();

        $kartu = $this->bagian($html, 'reference-price-latest');
        $this->assertStringContainsString('Rp 3.500/kg', $kartu);
        $this->assertStringContainsString('8 Nov 2026', $kartu);
        $this->assertStringContainsString('Rata-rata harga bank sampah', $kartu);

        $this->assertSame([$berlaku->id, $lama->id], $this->idRiwayat($html));
        $this->assertStringContainsString('Berlaku', $this->barisRiwayat($html, $berlaku));
        $this->assertStringContainsString('Riwayat', $this->barisRiwayat($html, $lama));
        $this->assertStringContainsString('Rp 3.200/kg', $this->barisRiwayat($html, $lama));

        $grafik = $this->grafik($html);
        $this->assertStringContainsString('role="img"', $grafik);
        $this->assertStringContainsString('aria-labelledby="trend-chart-title trend-chart-desc"', $grafik);
        $this->assertStringContainsString('<title id="trend-chart-title">Grafik tren harga acuan Plastik PET di Provinsi Alfa</title>', $grafik);
        $this->assertStringContainsString('<desc id="trend-chart-desc">Harga dari Rp 3.200/kg (1 Agt 2026) menjadi Rp 3.500/kg (8 Nov 2026), 2 data.</desc>', $grafik);
        $this->assertStringContainsString('<polyline', $grafik);
        $this->assertSame(['1 Agt 2026: Rp 3.200/kg', '8 Nov 2026: Rp 3.500/kg'], $this->titikGrafik($html));
        $this->assertSame(1, preg_match_all('/data-date-label>1 Agt 2026</', $grafik));
        $this->assertSame(1, preg_match_all('/data-date-label>8 Nov 2026</', $grafik));
        $this->assertGreaterThanOrEqual(3, substr_count($grafik, 'data-price-tick'));
        $this->assertStringNotContainsString('dusk="trend-chart-single"', $html);
    }

    public function test_warga_dan_admin_juga_bisa_membuka_detail(): void
    {
        $this->harga('2026-11-01');

        $this->actingAs(User::factory()->create())->get($this->detail())->assertOk()->assertSee('Rp 3.000/kg');
        $this->actingAs($this->admin)->get($this->detail())->assertOk()->assertSee('Rp 3.000/kg');
    }

    public function test_material_nonaktif_404(): void
    {
        $lama = Material::factory()->inactive()->create(['slug' => 'material-lama']);
        $this->harga('2026-11-01', material: $lama);

        $this->get($this->detail($lama))->assertNotFound();
    }

    public function test_region_kab_kota_404(): void
    {
        $kota = Region::create(['name' => 'Kota Contoh', 'code' => 'PA.01', 'parent_id' => $this->provinsiA->id]);
        $this->harga('2026-11-01');
        $this->harga('2026-11-01', provinsi: $kota);

        $this->get($this->detail(provinsi: $kota))->assertNotFound();
    }

    public function test_slug_atau_kode_yang_tidak_ada_404(): void
    {
        $this->harga('2026-11-01');

        $this->get('/harga-acuan/tidak-ada/PA')->assertNotFound();
        $this->get('/harga-acuan/plastik-pet/XX')->assertNotFound();
        $this->get('/harga-acuan/'.$this->plastik->id.'/'.$this->provinsiA->id)->assertNotFound();
    }

    public function test_pasangan_tanpa_harga_berlaku_404(): void
    {
        $provinsiB = Region::create(['name' => 'Provinsi Beta', 'code' => 'PB']);
        $provinsiC = Region::create(['name' => 'Provinsi Gamma', 'code' => 'PC']);
        $this->harga('2026-11-12', provinsi: $provinsiB); // hanya terjadwal
        $this->harga('2026-11-01'); // pasangan lain punya harga

        $this->get($this->detail(provinsi: $provinsiB))->assertNotFound();
        $this->get($this->detail(provinsi: $provinsiC))->assertNotFound();
    }

    public function test_riwayat_dan_grafik_tanpa_entri_terjadwal(): void
    {
        $berlaku = $this->harga('2026-11-01', 3000);
        $terjadwal = $this->harga('2026-11-20', 3900);

        $html = $this->get($this->detail())
            ->assertOk()
            ->assertDontSee('Rp 3.900/kg')
            ->assertDontSee('20 Nov 2026')
            ->assertDontSee('Terjadwal')
            ->getContent();

        $this->assertSame([$berlaku->id], $this->idRiwayat($html));
        $this->assertStringNotContainsString('reference-price-history-row-'.$terjadwal->id, $html);
        $this->assertSame(['1 Nov 2026: Rp 3.000/kg'], $this->titikGrafik($html));
    }

    public function test_koreksi_bertanggal_sama_tampil_dengan_label_yang_benar(): void
    {
        $lama = $this->harga('2026-10-01', 2900);
        $salah = $this->harga('2026-11-01', 3000);
        $koreksi = $this->harga('2026-11-01', 3100);

        $html = $this->get($this->detail())->assertOk()->getContent();

        $this->assertSame([$koreksi->id, $salah->id, $lama->id], $this->idRiwayat($html));
        $this->assertStringContainsString('Berlaku', $this->barisRiwayat($html, $koreksi));
        $this->assertStringContainsString('Riwayat', $this->barisRiwayat($html, $salah));
        $this->assertStringContainsString('Riwayat', $this->barisRiwayat($html, $lama));
        $this->assertStringContainsString('Rp 3.100/kg', $this->bagian($html, 'reference-price-latest'));

        // Grafik: satu titik per tanggal, memakai entri koreksi.
        $this->assertSame(['1 Okt 2026: Rp 2.900/kg', '1 Nov 2026: Rp 3.100/kg'], $this->titikGrafik($html));
    }

    public function test_batas_hari_mengikuti_wib(): void
    {
        $sekarang = $this->harga('2026-11-01', 3000);
        $besok = $this->harga('2026-11-12', 3600);

        $this->get($this->detail())->assertOk()->assertDontSee('Rp 3.600/kg');

        // 23.30 UTC tanggal 11 = 06.30 WIB tanggal 12.
        Carbon::setTestNow(Carbon::parse('2026-11-11 23:30:00', 'UTC'));

        $html = $this->get($this->detail())->assertOk()->getContent();

        $kartu = $this->bagian($html, 'reference-price-latest');
        $this->assertStringContainsString('Rp 3.600/kg', $kartu);
        $this->assertStringContainsString('12 Nov 2026', $kartu);

        $this->assertSame([$besok->id, $sekarang->id], $this->idRiwayat($html));
        $this->assertStringContainsString('Berlaku', $this->barisRiwayat($html, $besok));
        $this->assertStringContainsString('Riwayat', $this->barisRiwayat($html, $sekarang));

        $this->assertSame(['1 Nov 2026: Rp 3.000/kg', '12 Nov 2026: Rp 3.600/kg'], $this->titikGrafik($html));
    }

    public function test_jumlah_titik_grafik_sama_dengan_tanggal_berlaku_berbeda_maksimal_12(): void
    {
        // 14 tanggal bulanan, tanggal ke-5 punya koreksi, ditambah satu entri terjadwal.
        for ($i = 0; $i < 14; $i++) {
            $tanggal = CarbonImmutable::parse('2025-09-01')->addMonths($i)->toDateString();
            $this->harga($tanggal, 2000 + $i * 50);

            if ($i === 4) {
                $this->harga($tanggal, 2222);
            }
        }
        $this->harga('2026-12-01', 9999);

        $html = $this->get($this->detail())->assertOk()->getContent();
        $titik = $this->titikGrafik($html);

        $this->assertCount(12, $titik);
        $this->assertSame(12, substr_count($this->grafik($html), 'data-point'));
        $this->assertSame('1 Nov 2025: Rp 2.100/kg', $titik[0]);
        $this->assertSame('1 Jan 2026: Rp 2.222/kg', $titik[2]);
        $this->assertSame('1 Okt 2026: Rp 2.650/kg', $titik[11]);
        $this->assertStringContainsString('Harga dari Rp 2.100/kg (1 Nov 2025) menjadi Rp 2.650/kg (1 Okt 2026), 12 data.', $html);

        // 15 entri berlaku: tabel riwayat 15 baris di halaman pertama.
        $this->assertCount(15, $this->idRiwayat($html));
    }

    public function test_jumlah_titik_untuk_beberapa_tanggal(): void
    {
        foreach (['2026-08-01', '2026-09-01', '2026-09-01', '2026-10-01'] as $i => $tanggal) {
            $this->harga($tanggal, 3000 + $i * 100);
        }

        $html = $this->get($this->detail())->assertOk()->getContent();

        $this->assertSame(3, substr_count($this->grafik($html), 'data-point'));
        $this->assertCount(4, $this->idRiwayat($html));
    }

    public function test_satu_data_menampilkan_keterangan(): void
    {
        $this->harga('2026-11-08', 3500);

        $html = $this->get($this->detail())
            ->assertOk()
            ->assertSee('Baru ada satu data harga.')
            ->assertSee('dusk="trend-chart-single"', false)
            ->getContent();

        $grafik = $this->grafik($html);
        $this->assertSame(1, substr_count($grafik, 'data-point'));
        $this->assertStringNotContainsString('<polyline', $grafik);
        $this->assertStringContainsString('<desc id="trend-chart-desc">Harga Rp 3.500/kg sejak 8 Nov 2026, 1 data.</desc>', $grafik);
    }

    public function test_nama_penerbit_tidak_tampil(): void
    {
        $this->harga('2026-10-01');
        $this->harga('2026-11-01');

        $this->get($this->detail())->assertOk()->assertDontSee('Admin Penerbit Rahasia')->assertDontSee('Penerbit');
    }

    public function test_paginasi_riwayat_15_per_halaman(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->harga(sprintf('2026-10-%02d', $i), 1000 + $i);
        }

        $this->get($this->detail())
            ->assertOk()
            ->assertSee('Rp 1.017/kg')
            ->assertSee('Rp 1.003/kg')
            ->assertSee(e($this->detail(query: ['page' => 2])), false);

        $html = $this->get($this->detail(query: ['page' => 2]))
            ->assertOk()
            ->assertSee('halaman 2 dari 2')
            ->getContent();

        $this->assertCount(2, $this->idRiwayat($html));
        $this->assertStringNotContainsString('Berlaku</span>', $this->bagian($html, 'reference-price-history-table', 'table'));

        $this->get($this->detail(query: ['page' => 9]))->assertRedirect($this->detail(query: ['page' => 2]));
    }

    public function test_tautan_kembali_membawa_filter_yang_valid(): void
    {
        $this->harga('2026-11-01');

        $this->get($this->detail(query: ['kembali' => 'material=5&provinsi=7&page=2']))
            ->assertOk()
            ->assertSee('href="'.e(route('reference-prices.index', ['material' => 5, 'provinsi' => 7, 'page' => 2])).'"', false);

        // Paginasi riwayat tetap membawa parameter kembali.
        for ($i = 1; $i <= 16; $i++) {
            $this->harga(sprintf('2026-10-%02d', $i));
        }

        $this->get($this->detail(query: ['kembali' => 'material=5']))
            ->assertOk()
            ->assertSee(e($this->detail(query: ['kembali' => 'material=5', 'page' => 2])), false);
    }

    public function test_tautan_kembali_mengabaikan_nilai_yang_tidak_valid(): void
    {
        $this->harga('2026-11-01');

        $percobaan = [
            'https://contoh.invalid/curang',
            'material=abc&provinsi=-1&page=0',
            'material[]=1&lain=2',
            'page=1',
        ];

        foreach ($percobaan as $kembali) {
            $html = $this->get($this->detail(query: ['kembali' => $kembali]))->assertOk()->getContent();

            $this->assertSame(1, preg_match('/<a\b[^>]*href="([^"]*)"[^>]*dusk="reference-price-back"/', $html, $cocok));
            $this->assertSame(route('reference-prices.index'), html_entity_decode($cocok[1]), "kembali={$kembali}");
        }

        $this->get($this->detail(query: ['kembali' => ['material' => '1']]))->assertOk();
    }

    public function test_jumlah_query_detail_tidak_bertambah_per_baris(): void
    {
        $this->harga('2026-10-01');
        $this->harga('2026-11-01');

        $hitung = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get($this->detail())->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $hitung();
        $denganDua = $hitung();

        for ($i = 1; $i <= 13; $i++) {
            ReferencePrice::factory()->forMaterial($this->plastik)->forRegion($this->provinsiA)->effectiveOn(sprintf('2026-09-%02d', $i))->create();
        }

        $this->assertSame($denganDua, $hitung());
    }
}
