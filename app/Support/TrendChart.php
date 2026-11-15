<?php

namespace App\Support;

use App\Models\ReferencePrice;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Perhitungan koordinat grafik tren harga (rupiah bulat) untuk SVG yang dirender server, tanpa JS.
 *
 * - Satu titik per tanggal. Bila ada tanggal ganda, data yang datang paling akhir yang dipakai.
 * - Hanya MAX_POINTS tanggal terakhir yang dipakai, diurutkan dari lama (kiri) ke baru (kanan).
 * - Posisi x sebanding dengan selisih hari; satu titik diletakkan di tengah.
 * - Sumbu harga memakai 3–5 nilai bulat yang rapi. Bila semua harga sama (termasuk satu titik),
 *   rentang sumbu dibuat simetris sehingga garis datar tepat di tengah, tanpa pembagian nol.
 *
 * Ukuran: viewBox WIDTH × HEIGHT dengan teks FONT_SIZE. SVG ditampilkan selebar wadahnya, jadi teks
 * ikut diskalakan: di layar 360px grafik selebar ±296px (teks ±10,4px), dan lebar maksimum 480px
 * di desktop (teks ±16,9px). Kelas ini tidak memuat teks terjemahan maupun kelas CSS.
 */
final class TrendChart
{
    public const MAX_POINTS = 12;

    public const WIDTH = 340;

    public const HEIGHT = 200;

    public const FONT_SIZE = 12;

    public const POINT_RADIUS = 4;

    /**
     * Perkiraan lebar satu karakter label relatif terhadap FONT_SIZE (sedikit dilebihkan supaya aman).
     */
    private const CHAR_WIDTH = 0.6;

    private const PADDING_TOP = 12;

    private const PADDING_RIGHT = 12;

    private const PADDING_LEFT = 2;

    /**
     * Ruang di bawah area plot untuk label tanggal.
     */
    private const PADDING_BOTTOM = 30;

    /**
     * Jarak label harga ke area plot.
     */
    private const LABEL_GAP = 8;

    private const MIN_TICKS = 3;

    private const MAX_TICKS = 5;

    /**
     * @var list<array{date: CarbonImmutable, price: int}>
     */
    private array $data;

    /**
     * @var list<int>
     */
    private array $tickValues;

    private int $domainMin;

    private int $domainMax;

    private float $plotLeft;

    /**
     * @param  iterable<array{date: CarbonInterface, price: int}>  $points
     *
     * @throws InvalidArgumentException bila tidak ada data
     */
    public function __construct(iterable $points)
    {
        $byDate = [];

        foreach ($points as $point) {
            $date = CarbonImmutable::instance($point['date']);
            $byDate[$date->format('Y-m-d')] = ['date' => $date, 'price' => (int) $point['price']];
        }

        if ($byDate === []) {
            throw new InvalidArgumentException('Grafik tren butuh minimal satu data harga.');
        }

        ksort($byDate);

        $this->data = array_values(array_slice($byDate, -self::MAX_POINTS));

        $prices = array_column($this->data, 'price');
        [$this->domainMin, $this->domainMax, $this->tickValues] = self::scale(min($prices), max($prices));

        $longestLabel = max(array_map(fn (int $value) => mb_strlen(IndonesianFormat::rupiah($value)), $this->tickValues));
        $this->plotLeft = self::PADDING_LEFT + $longestLabel * self::FONT_SIZE * self::CHAR_WIDTH + self::LABEL_GAP;
    }

    /**
     * @param  iterable<ReferencePrice>  $prices
     */
    public static function fromReferencePrices(iterable $prices): self
    {
        $points = [];

        foreach ($prices as $price) {
            $points[] = ['date' => $price->effective_date, 'price' => $price->price_per_kg];
        }

        return new self($points);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function isSinglePoint(): bool
    {
        return $this->count() === 1;
    }

    /**
     * Data paling lama yang ditampilkan.
     *
     * @return array{date: CarbonImmutable, price: int}
     */
    public function first(): array
    {
        return $this->data[0];
    }

    /**
     * Data paling baru.
     *
     * @return array{date: CarbonImmutable, price: int}
     */
    public function last(): array
    {
        return $this->data[$this->count() - 1];
    }

    /**
     * Titik grafik, urut kronologis.
     *
     * @return list<array{x: float, y: float, date: CarbonImmutable, price: int}>
     */
    public function points(): array
    {
        $firstDay = self::dayNumber($this->first()['date']);
        $span = self::dayNumber($this->last()['date']) - $firstDay;

        return array_map(fn (array $point) => [
            'x' => $span === 0
                ? $this->round($this->plotLeft + $this->plotWidth() / 2)
                : $this->round($this->plotLeft + (self::dayNumber($point['date']) - $firstDay) / $span * $this->plotWidth()),
            'y' => $this->yFor($point['price']),
            'date' => $point['date'],
            'price' => $point['price'],
        ], $this->data);
    }

    /**
     * Nilai atribut points untuk <polyline>, misalnya "62,170 328,12".
     */
    public function polylinePoints(): string
    {
        return implode(' ', array_map(fn (array $point) => $point['x'].','.$point['y'], $this->points()));
    }

    /**
     * Garis bantu dan label sumbu harga, dari nilai terkecil (bawah) ke terbesar (atas).
     *
     * @return list<array{value: int, label: string, y: float, labelX: float, labelY: float}>
     */
    public function priceTicks(): array
    {
        return array_map(function (int $value) {
            $y = $this->yFor($value);

            return [
                'value' => $value,
                'label' => IndonesianFormat::rupiah($value),
                'y' => $y,
                'labelX' => $this->round($this->plotLeft - self::LABEL_GAP),
                // Garis dasar teks digeser ke bawah supaya label sejajar tengah dengan garis bantu.
                'labelY' => $this->round($y + self::FONT_SIZE * 0.35),
            ];
        }, $this->tickValues);
    }

    /**
     * Label sumbu tanggal: tanggal pertama dan terakhir saja (atau satu label di tengah untuk satu titik),
     * supaya tidak bertumpuk di layar sempit.
     *
     * @return list<array{label: string, x: float, y: float, anchor: string}>
     */
    public function dateLabels(): array
    {
        $y = $this->round($this->plotBottom() + self::FONT_SIZE + 8);
        $points = $this->points();

        if ($this->isSinglePoint()) {
            return [['label' => IndonesianFormat::date($points[0]['date']), 'x' => $points[0]['x'], 'y' => $y, 'anchor' => 'middle']];
        }

        $last = $points[count($points) - 1];

        return [
            ['label' => IndonesianFormat::date($points[0]['date']), 'x' => $points[0]['x'], 'y' => $y, 'anchor' => 'start'],
            ['label' => IndonesianFormat::date($last['date']), 'x' => $last['x'], 'y' => $y, 'anchor' => 'end'],
        ];
    }

    public function plotLeft(): float
    {
        return $this->round($this->plotLeft);
    }

    public function plotRight(): float
    {
        return (float) (self::WIDTH - self::PADDING_RIGHT);
    }

    public function plotTop(): float
    {
        return (float) self::PADDING_TOP;
    }

    public function plotBottom(): float
    {
        return (float) (self::HEIGHT - self::PADDING_BOTTOM);
    }

    private function plotWidth(): float
    {
        return $this->plotRight() - $this->plotLeft;
    }

    private function yFor(int $value): float
    {
        $ratio = ($this->domainMax - $value) / ($this->domainMax - $this->domainMin);

        return $this->round($this->plotTop() + $ratio * ($this->plotBottom() - $this->plotTop()));
    }

    private function round(float $value): float
    {
        return round($value, 1);
    }

    /**
     * Nomor hari dari tanggal kalender (tanpa jam dan zona waktu).
     */
    private static function dayNumber(CarbonImmutable $date): int
    {
        return intdiv(CarbonImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'), 'UTC')->getTimestamp(), 86_400);
    }

    /**
     * Rentang sumbu harga dan nilai label-nya.
     *
     * @return array{0: int, 1: int, 2: list<int>} [batas bawah, batas atas, nilai label naik]
     */
    private static function scale(int $min, int $max): array
    {
        if ($min === $max) {
            // Rentang simetris di sekitar harga supaya garis datar berada tepat di tengah.
            $step = self::niceStep(max(1, $min / 10));
            $half = $min - 2 * $step >= 0 ? 2 * $step : $min;
            $low = $min - $half;
            $high = $min + $half;

            $ticks = [];
            for ($value = (int) ceil($low / $step) * $step; $value <= $high; $value += $step) {
                $ticks[] = $value;
            }

            return [$low, $high, $ticks];
        }

        $step = self::niceStep(($max - $min) / (self::MAX_TICKS - 1));

        while (true) {
            $low = intdiv($min, $step) * $step;
            $high = (int) ceil($max / $step) * $step;

            if (($high - $low) / $step + 1 <= self::MAX_TICKS) {
                break;
            }

            $step = self::niceStep($step + 1);
        }

        while (($high - $low) / $step + 1 < self::MIN_TICKS) {
            $high += $step;
        }

        return [$low, $high, range($low, $high, $step)];
    }

    /**
     * Langkah bulat terkecil dari deret 1, 2, 2,5, 5 × 10^n yang >= $atLeast.
     */
    private static function niceStep(float $atLeast): int
    {
        for ($magnitude = 1; ; $magnitude *= 10) {
            foreach ([1, 2, 2.5, 5] as $factor) {
                $step = (float) ($factor * $magnitude);

                // 2,5 hanya dipakai bila hasilnya bulat (25, 250, ...).
                if ($step === floor($step) && $step >= $atLeast) {
                    return (int) $step;
                }
            }
        }
    }
}
