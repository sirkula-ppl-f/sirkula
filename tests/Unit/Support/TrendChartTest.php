<?php

namespace Tests\Unit\Support;

use App\Support\TrendChart;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrendChartTest extends TestCase
{
    /**
     * @param  array<string, int>  $hargaPerTanggal  'Y-m-d' => harga
     */
    private function grafik(array $hargaPerTanggal): TrendChart
    {
        $data = [];

        foreach ($hargaPerTanggal as $tanggal => $harga) {
            $data[] = ['date' => CarbonImmutable::parse($tanggal, 'Asia/Jakarta'), 'price' => $harga];
        }

        return new TrendChart($data);
    }

    private function tengahPlotX(TrendChart $grafik): float
    {
        return round(($grafik->plotLeft() + $grafik->plotRight()) / 2, 1);
    }

    private function tengahPlotY(TrendChart $grafik): float
    {
        return round(($grafik->plotTop() + $grafik->plotBottom()) / 2, 1);
    }

    /**
     * @return list<int>
     */
    private function nilaiSumbu(TrendChart $grafik): array
    {
        return array_column($grafik->priceTicks(), 'value');
    }

    public function test_satu_titik_di_tengah_dengan_sumbu_tetap_berlabel(): void
    {
        $grafik = $this->grafik(['2026-11-08' => 3500]);

        $this->assertTrue($grafik->isSinglePoint());
        $this->assertSame(1, $grafik->count());

        [$titik] = $grafik->points();
        $this->assertEqualsWithDelta($this->tengahPlotX($grafik), $titik['x'], 0.1);
        $this->assertEqualsWithDelta($this->tengahPlotY($grafik), $titik['y'], 0.1);

        $this->assertSame([2500, 3000, 3500, 4000, 4500], $this->nilaiSumbu($grafik));
        $this->assertSame('Rp 3.500', $grafik->priceTicks()[2]['label']);

        $this->assertSame([['label' => '8 Nov 2026', 'x' => $titik['x'], 'y' => $grafik->dateLabels()[0]['y'], 'anchor' => 'middle']], $grafik->dateLabels());
    }

    public function test_satu_titik_harga_terkecil_tidak_membuat_sumbu_negatif(): void
    {
        $grafik = $this->grafik(['2026-11-08' => 1]);

        $this->assertSame([0, 1, 2], $this->nilaiSumbu($grafik));
        $this->assertEqualsWithDelta($this->tengahPlotY($grafik), $grafik->points()[0]['y'], 0.1);
    }

    public function test_semua_harga_sama_membentuk_garis_datar_di_tengah(): void
    {
        $grafik = $this->grafik(['2026-09-01' => 3550, '2026-10-01' => 3550, '2026-11-01' => 3550]);

        $y = array_unique(array_column($grafik->points(), 'y'));
        $this->assertCount(1, $y);
        $this->assertEqualsWithDelta($this->tengahPlotY($grafik), reset($y), 0.1);

        // Nilai sumbu tetap bulat rapi walaupun harganya tidak bulat.
        $this->assertSame([3000, 3500, 4000, 4500], $this->nilaiSumbu($grafik));
    }

    public function test_lebih_dari_12_tanggal_dipotong_menjadi_12_tanggal_terakhir(): void
    {
        // 14 tanggal bulanan (Jan 2025 – Feb 2026), sengaja dalam urutan terbalik.
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $data[CarbonImmutable::parse('2025-01-01')->addMonths($i)->format('Y-m-d')] = 1000 + $i;
        }

        $grafik = $this->grafik($data);
        $titik = $grafik->points();

        $this->assertSame(12, TrendChart::MAX_POINTS);
        $this->assertSame(12, $grafik->count());
        $this->assertCount(12, $titik);
        $this->assertSame('2025-03-01', $grafik->first()['date']->format('Y-m-d'));
        $this->assertSame('2026-02-01', $grafik->last()['date']->format('Y-m-d'));
        $this->assertSame(range(1002, 1013), array_column($titik, 'price'));
    }

    public function test_titik_diurutkan_kronologis_dari_kiri_ke_kanan(): void
    {
        $grafik = $this->grafik([
            '2026-11-08' => 3500,
            '2026-08-01' => 3200,
            '2026-10-01' => 3400,
            '2026-09-01' => 3300,
        ]);

        $titik = $grafik->points();

        $this->assertSame(['2026-08-01', '2026-09-01', '2026-10-01', '2026-11-08'], array_map(fn ($t) => $t['date']->format('Y-m-d'), $titik));
        $this->assertSame([3200, 3300, 3400, 3500], array_column($titik, 'price'));

        $x = array_column($titik, 'x');
        $urut = $x;
        sort($urut);
        $this->assertSame($urut, $x);
        $this->assertSame($grafik->plotLeft(), $x[0]);
        $this->assertSame($grafik->plotRight(), $x[3]);

        // Harga naik → titik makin tinggi (y makin kecil).
        $y = array_column($titik, 'y');
        $turun = $y;
        rsort($turun);
        $this->assertSame($turun, $y);

        $this->assertSame([
            ['label' => '1 Agt 2026', 'anchor' => 'start'],
            ['label' => '8 Nov 2026', 'anchor' => 'end'],
        ], array_map(fn ($l) => ['label' => $l['label'], 'anchor' => $l['anchor']], $grafik->dateLabels()));

        $this->assertSame(implode(' ', array_map(fn ($t) => $t['x'].','.$t['y'], $titik)), $grafik->polylinePoints());
    }

    public function test_posisi_x_sebanding_dengan_selisih_hari(): void
    {
        $grafik = $this->grafik(['2026-01-01' => 1000, '2026-01-11' => 1100, '2026-01-31' => 1200]);

        [$a, $b, $c] = array_column($grafik->points(), 'x');

        $this->assertEqualsWithDelta(($c - $a) / 3, $b - $a, 0.2);
    }

    public function test_tanggal_ganda_memakai_data_yang_datang_terakhir(): void
    {
        $grafik = new TrendChart([
            ['date' => CarbonImmutable::parse('2026-11-01'), 'price' => 3000],
            ['date' => CarbonImmutable::parse('2026-11-01'), 'price' => 3100],
        ]);

        $this->assertSame(1, $grafik->count());
        $this->assertSame(3100, $grafik->last()['price']);
    }

    /**
     * @return array<string, array{0: array<string, int>}>
     */
    public static function kumpulanData(): array
    {
        return [
            'satu titik' => [['2026-11-08' => 3500]],
            'harga sama' => [['2026-10-01' => 950, '2026-11-01' => 950]],
            'harga naik turun' => [['2026-06-01' => 3010, '2026-07-01' => 3990, '2026-08-01' => 3500, '2026-09-01' => 3200]],
            'rentang sempit' => [['2026-10-01' => 3000, '2026-11-01' => 3001]],
            'rentang lebar' => [['2025-01-01' => 1, '2026-11-01' => 10_000_000]],
            'harga besar sama' => [['2026-11-01' => 10_000_000]],
            '12 tanggal' => [array_combine(
                array_map(fn ($i) => sprintf('2026-%02d-15', $i), range(1, 12)),
                [1200, 1250, 1300, 1280, 1400, 1390, 1420, 1500, 1480, 1550, 1600, 1580],
            )],
        ];
    }

    /**
     * @param  array<string, int>  $data
     */
    #[DataProvider('kumpulanData')]
    public function test_semua_koordinat_di_dalam_viewbox(array $data): void
    {
        $grafik = $this->grafik($data);

        $dalam = function (float $x, float $y, string $apa): void {
            $this->assertGreaterThanOrEqual(0, $x, "{$apa}: x di luar viewBox.");
            $this->assertLessThanOrEqual(TrendChart::WIDTH, $x, "{$apa}: x di luar viewBox.");
            $this->assertGreaterThanOrEqual(0, $y, "{$apa}: y di luar viewBox.");
            $this->assertLessThanOrEqual(TrendChart::HEIGHT, $y, "{$apa}: y di luar viewBox.");
        };

        foreach ($grafik->points() as $titik) {
            $dalam($titik['x'] - TrendChart::POINT_RADIUS, $titik['y'] - TrendChart::POINT_RADIUS, 'titik');
            $dalam($titik['x'] + TrendChart::POINT_RADIUS, $titik['y'] + TrendChart::POINT_RADIUS, 'titik');
            $this->assertGreaterThanOrEqual($grafik->plotLeft(), $titik['x']);
            $this->assertLessThanOrEqual($grafik->plotRight(), $titik['x']);
            $this->assertGreaterThanOrEqual($grafik->plotTop(), $titik['y']);
            $this->assertLessThanOrEqual($grafik->plotBottom(), $titik['y']);
        }

        foreach ($grafik->priceTicks() as $tick) {
            $dalam($tick['labelX'], $tick['labelY'], 'label harga');
            $dalam($tick['labelX'], $tick['y'], 'garis bantu');
            // Perkiraan lebar teks label (rata kanan) tidak keluar dari sisi kiri.
            $this->assertGreaterThanOrEqual(0, $tick['labelX'] - mb_strlen($tick['label']) * TrendChart::FONT_SIZE * 0.6);
        }

        foreach ($grafik->dateLabels() as $label) {
            $dalam($label['x'], $label['y'], 'label tanggal');
        }
    }

    /**
     * @param  array<string, int>  $data
     */
    #[DataProvider('kumpulanData')]
    public function test_label_sumbu_harga_3_sampai_5_nilai_bulat_terurut(array $data): void
    {
        $grafik = $this->grafik($data);
        $nilai = $this->nilaiSumbu($grafik);

        $this->assertGreaterThanOrEqual(3, count($nilai));
        $this->assertLessThanOrEqual(5, count($nilai));

        $urut = $nilai;
        sort($urut);
        $this->assertSame($urut, $nilai, 'Nilai sumbu harga harus naik.');
        $this->assertGreaterThanOrEqual(0, $nilai[0]);

        // Jarak antarnilai sama dan berupa angka rapi (1, 2, 2,5, atau 5 × 10^n).
        $jarak = array_unique(array_map(fn ($a, $b) => $b - $a, array_slice($nilai, 0, -1), array_slice($nilai, 1)));
        $this->assertCount(1, $jarak);
        $langkah = reset($jarak);
        $this->assertMatchesRegularExpression('/^(1|2|25|5)0*$/', (string) $langkah);

        // Garis bantu: nilai lebih besar berada lebih tinggi.
        $y = array_column($grafik->priceTicks(), 'y');
        $turun = $y;
        rsort($turun);
        $this->assertSame($turun, $y);
    }

    public function test_data_kosong_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TrendChart([]);
    }
}
