<?php

namespace App\Services;

use App\Models\Hub;
use App\Models\Region;
use Carbon\CarbonImmutable;

/**
 * Layanan perhitungan Dashboard Monitoring Admin dan Ekspor CSV (PBI-21 / FR-022).
 *
 * Seluruh metrik transaksi dihitung hanya dari pesanan berstatus PAID (Lunas),
 * tanpa penghitungan ganda pesanan, berat yang tidak diketahui tidak dianggap 0 kg,
 * dan sel CSV dinetralkan dari karakter pemicu formula spreadsheet.
 */
class AdminMonitoringService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const DEFAULT_MONTHS = 6;

    public const PERIOD_ALL = 'all';

    public const PERIOD_THIS_MONTH = 'this_month';

    public const PERIOD_LAST_3_MONTHS = 'last_3_months';

    public const PERIOD_THIS_YEAR = 'this_year';

    /**
     * Karakter pembuka sel yang dapat dieksekusi sebagai formula oleh aplikasi spreadsheet.
     *
     * @var list<string>
     */
    private const CSV_FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * Daftar opsi filter periode yang didukung.
     *
     * @return array<string, string>
     */
    public function periodOptions(): array
    {
        return [
            self::PERIOD_ALL => 'Semua Waktu',
            self::PERIOD_THIS_MONTH => 'Bulan Ini',
            self::PERIOD_LAST_3_MONTHS => '3 Bulan Terakhir',
            self::PERIOD_THIS_YEAR => 'Tahun Ini',
        ];
    }

    /**
     * Daftar opsi filter wilayah (urut nama).
     *
     * @return array<int, string>
     */
    public function regionOptions(): array
    {
        return Region::query()
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Daftar opsi filter hub komunitas (urut nama).
     *
     * @return array<int, string>
     */
    public function hubOptions(): array
    {
        return Hub::query()
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }

    public function normalizePeriod(?string $period): string
    {
        return array_key_exists((string) $period, $this->periodOptions())
            ? (string) $period
            : self::PERIOD_ALL;
    }

    public function normalizeRegionId(mixed $regionId): ?int
    {
        if (! $this->isPositiveInt($regionId)) {
            return null;
        }

        $id = (int) $regionId;

        return Region::query()->whereKey($id)->exists() ? $id : null;
    }

    public function normalizeHubId(mixed $hubId): ?int
    {
        if (! $this->isPositiveInt($hubId)) {
            return null;
        }

        $id = (int) $hubId;

        return Hub::query()->whereKey($id)->exists() ? $id : null;
    }

    /**
     * Normalisasi seluruh filter input query string sekaligus.
     *
     * @param  array{period?: mixed, periode?: mixed, region_id?: mixed, hub_id?: mixed}  $raw
     * @return array{period: string, region_id: int|null, hub_id: int|null}
     */
    public function normalizeFilters(array $raw = []): array
    {
        $rawPeriod = $raw['period'] ?? $raw['periode'] ?? null;

        return [
            'period' => $this->normalizePeriod(is_string($rawPeriod) ? $rawPeriod : null),
            'region_id' => $this->normalizeRegionId($raw['region_id'] ?? null),
            'hub_id' => $this->normalizeHubId($raw['hub_id'] ?? null),
        ];
    }

    /**
     * Netralkan nilai sel CSV agar tidak dieksekusi sebagai formula spreadsheet (FR-022 / NFR-009).
     *
     * - Nilai numerik (int/float) atau string angka murni (termasuk angka negatif seperti "-12")
     *   dipertahankan apa adanya.
     * - String yang diawali karakter formula (=, +, -, @, tab, CR, LF) baik langsung maupun
     *   setelah spasi awal diberi prefix apostrof (').
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $text = (string) $value;

        if ($text === '') {
            return '';
        }

        $trimmedLeft = ltrim($text, " \t\r\n");

        foreach (self::CSV_FORMULA_PREFIXES as $prefix) {
            if (str_starts_with($text, $prefix) || ($trimmedLeft !== '' && str_starts_with($trimmedLeft, $prefix))) {
                return "'".$text;
            }
        }

        return $text;
    }

    /**
     * Netralkan satu baris CSV.
     *
     * @param  iterable<mixed>  $row
     * @return list<string>
     */
    public static function sanitizeCsvRow(iterable $row): array
    {
        $sanitized = [];

        foreach ($row as $cell) {
            $sanitized[] = self::sanitizeCsvCell($cell);
        }

        return $sanitized;
    }

    /**
     * Rentang waktu UTC berdasarkan batas bulan/tahun di WIB (Asia/Jakarta).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    protected function periodRange(string $period): ?array
    {
        $nowWib = CarbonImmutable::now(self::TIMEZONE);

        return match ($period) {
            self::PERIOD_THIS_MONTH => [
                $nowWib->startOfMonth()->utc(),
                $nowWib->endOfMonth()->utc(),
            ],
            self::PERIOD_LAST_3_MONTHS => [
                $nowWib->startOfMonth()->subMonths(2)->utc(),
                $nowWib->endOfMonth()->utc(),
            ],
            self::PERIOD_THIS_YEAR => [
                $nowWib->startOfYear()->utc(),
                $nowWib->endOfYear()->utc(),
            ],
            default => null,
        };
    }

    private function isPositiveInt(mixed $value): bool
    {
        return (is_int($value) && $value > 0)
            || (is_string($value) && ctype_digit($value) && (int) $value > 0);
    }
}
