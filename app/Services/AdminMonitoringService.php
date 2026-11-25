<?php

namespace App\Services;

use App\Enums\ListingType;
use App\Enums\UserStatus;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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

    public const UNKNOWN_REGION_LABEL = 'Wilayah Belum Tercatat';

    public const UNKNOWN_HUB_LABEL = 'Tanpa Hub / Belum Terkait';

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
     * Seluruh data monitoring platform untuk Dashboard Admin (FR-022).
     *
     * @param  array{period?: mixed, periode?: mixed, region_id?: mixed, hub_id?: mixed}  $rawFilters
     * @return array{
     *     filters: array{period: string, region_id: int|null, hub_id: int|null},
     *     kpis: array{
     *         active_listings_count: int,
     *         active_users_count: int,
     *         total_paid_orders: int,
     *         total_items_quantity: int,
     *         total_amount: int,
     *         known_weight_kg: float|null,
     *         items_with_weight_count: int,
     *         items_without_weight_count: int
     *     },
     *     by_type: array<string, array{
     *         type: ListingType,
     *         label: string,
     *         orders_count: int,
     *         quantity_total: int,
     *         subtotal_amount: int,
     *         known_weight_kg: float|null,
     *         items_with_weight_count: int,
     *         items_without_weight_count: int
     *     }>,
     *     monthly_series: list<array{
     *         month_key: string,
     *         label: string,
     *         orders_count: int,
     *         quantity_total: int,
     *         total_amount: int,
     *         known_weight_kg: float|null,
     *         items_with_weight_count: int,
     *         items_without_weight_count: int,
     *         height_percent: int
     *     }>,
     *     by_region: list<array{
     *         region_id: int|null,
     *         region_name: string,
     *         orders_count: int,
     *         quantity_total: int,
     *         subtotal_amount: int,
     *         known_weight_kg: float|null,
     *         items_with_weight_count: int,
     *         items_without_weight_count: int
     *     }>,
     *     by_hub: list<array{
     *         region_id: int|null,
     *         region_name: string,
     *         hub_id: int|null,
     *         hub_name: string,
     *         orders_count: int,
     *         quantity_total: int,
     *         subtotal_amount: int,
     *         known_weight_kg: float|null,
     *         items_with_weight_count: int,
     *         items_without_weight_count: int
     *     }>
     * }
     */
    public function getDashboardData(array $rawFilters = []): array
    {
        $filters = $this->normalizeFilters($rawFilters);
        $byType = $this->aggregateByType($filters);

        $totalItemsQuantity = 0;
        $totalKnownWeight = 0.0;
        $totalWithWeight = 0;
        $totalWithoutWeight = 0;

        foreach ($byType as $typeSummary) {
            $totalItemsQuantity += $typeSummary['quantity_total'];
            $totalWithWeight += $typeSummary['items_with_weight_count'];
            $totalWithoutWeight += $typeSummary['items_without_weight_count'];

            if ($typeSummary['known_weight_kg'] !== null) {
                $totalKnownWeight += $typeSummary['known_weight_kg'];
            }
        }

        $orderTotals = $this->summarizePaidOrders($filters);

        return [
            'filters' => $filters,
            'kpis' => [
                'active_listings_count' => $this->countActiveListings($filters['region_id'], $filters['hub_id']),
                'active_users_count' => $this->countActiveUsers($filters['region_id']),
                'total_paid_orders' => $orderTotals['total_paid_orders'],
                'total_items_quantity' => $totalItemsQuantity,
                'total_amount' => $orderTotals['total_amount'],
                // Berat yang tidak diketahui tidak dianggap 0 kg (FR-022).
                'known_weight_kg' => $totalWithWeight > 0 ? round($totalKnownWeight, 2) : null,
                'items_with_weight_count' => $totalWithWeight,
                'items_without_weight_count' => $totalWithoutWeight,
            ],
            'by_type' => $byType,
            'monthly_series' => $this->monthlySeries($filters, self::DEFAULT_MONTHS),
            'by_region' => $this->aggregateByRegion($filters),
            'by_hub' => $this->aggregateByRegionAndHub($filters),
        ];
    }

    /**
     * Jumlah listing aktif (memenuhi syarat tayang katalog: APPROVED, ACTIVE, stok > 0, penjual & hub aktif).
     */
    public function countActiveListings(?int $regionId = null, ?int $hubId = null): int
    {
        $query = Listing::purchasable();

        if ($regionId !== null) {
            $query->where(function (Builder $q) use ($regionId) {
                $q->where('listings.region_id', $regionId)
                    ->orWhere(function (Builder $fallbackHub) use ($regionId) {
                        $fallbackHub->whereNull('listings.region_id')
                            ->whereHas('hub', fn (Builder $hub) => $hub->where('region_id', $regionId));
                    })
                    ->orWhere(function (Builder $fallbackSeller) use ($regionId) {
                        $fallbackSeller->whereNull('listings.region_id')
                            ->whereNull('listings.hub_id')
                            ->whereHas('seller', fn (Builder $seller) => $seller->where('region_id', $regionId));
                    });
            });
        }

        if ($hubId !== null) {
            $query->where('listings.hub_id', $hubId);
        }

        return $query->count();
    }

    /**
     * Jumlah pengguna berstatus ACTIVE (FR-004, FR-022).
     */
    public function countActiveUsers(?int $regionId = null): int
    {
        $query = User::query()->where('status', UserStatus::Active);

        if ($regionId !== null) {
            $query->where('region_id', $regionId);
        }

        return $query->count();
    }

    /**
     * Agregat pesanan lunas unik dan total nilai transaksi tanpa penghitungan ganda.
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     * @return array{total_paid_orders: int, total_amount: int}
     */
    protected function summarizePaidOrders(array $filters): array
    {
        if ($filters['region_id'] === null && $filters['hub_id'] === null) {
            $query = Order::query()->where('orders.status', Order::STATUS_PAID);
            $this->applyPeriodFilter($query, $filters['period'], 'orders');

            $row = $query
                ->selectRaw('COUNT(*) as total_paid_orders, COALESCE(SUM(total), 0) as total_amount')
                ->first();

            return [
                'total_paid_orders' => (int) ($row->total_paid_orders ?? 0),
                'total_amount' => (int) ($row->total_amount ?? 0),
            ];
        }

        $row = $this->basePaidOrderItemsQuery($filters)
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as total_paid_orders')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_amount')
            ->first();

        return [
            'total_paid_orders' => (int) ($row->total_paid_orders ?? 0),
            'total_amount' => (int) ($row->total_amount ?? 0),
        ];
    }

    /**
     * Agregat transaksi lunas per tipe listing (PRELOVED, RECYCLABLE, UPCYCLE).
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     * @return array<string, array{
     *     type: ListingType,
     *     label: string,
     *     orders_count: int,
     *     quantity_total: int,
     *     subtotal_amount: int,
     *     known_weight_kg: float|null,
     *     items_with_weight_count: int,
     *     items_without_weight_count: int
     * }>
     */
    public function aggregateByType(array $filters): array
    {
        $rows = $this->basePaidOrderItemsQuery($filters)
            ->selectRaw('order_items.type_snapshot as type_snapshot')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_total')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as subtotal_amount')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN order_items.quantity * order_items.weight_per_unit_snapshot ELSE 0 END) as known_weight_sum')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN 1 ELSE 0 END) as with_weight_count')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NULL THEN 1 ELSE 0 END) as without_weight_count')
            ->groupBy('order_items.type_snapshot')
            ->get()
            ->keyBy(fn (object $row) => (string) $row->type_snapshot);

        $byType = [];

        foreach (ListingType::cases() as $listingType) {
            $row = $rows->get($listingType->value);
            $withWeight = (int) ($row->with_weight_count ?? 0);
            $withoutWeight = (int) ($row->without_weight_count ?? 0);

            $byType[$listingType->value] = [
                'type' => $listingType,
                'label' => $listingType->label(),
                'orders_count' => (int) ($row->orders_count ?? 0),
                'quantity_total' => (int) ($row->quantity_total ?? 0),
                'subtotal_amount' => (int) ($row->subtotal_amount ?? 0),
                'known_weight_kg' => $withWeight > 0 ? round((float) $row->known_weight_sum, 2) : null,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => $withoutWeight,
            ];
        }

        return $byType;
    }

    /**
     * Deret grafik transaksi lunas bulanan dalam N bulan terakhir (urut lama ke baru, WIB).
     *
     * Pengelompokan bulan dilakukan di PHP berdasarkan orders.paid_at dalam WIB agar
     * konsisten di MySQL maupun SQLite tanpa ketergantungan tabel timezone database.
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     * @return list<array{
     *     month_key: string,
     *     label: string,
     *     orders_count: int,
     *     quantity_total: int,
     *     total_amount: int,
     *     known_weight_kg: float|null,
     *     items_with_weight_count: int,
     *     items_without_weight_count: int,
     *     height_percent: int
     * }>
     */
    public function monthlySeries(array $filters, int $months = self::DEFAULT_MONTHS): array
    {
        $months = max(1, min(12, $months));

        $currentMonthWib = CarbonImmutable::now(self::TIMEZONE)->startOfMonth();
        $startMonthWib = $currentMonthWib->subMonths($months - 1);
        $endMonthWib = $currentMonthWib->endOfMonth();

        $buckets = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $startMonthWib->addMonths($i);
            $monthKey = $month->format('Y-m');

            $buckets[$monthKey] = [
                'month_key' => $monthKey,
                'label' => $month->locale('id')->translatedFormat('M Y'),
                'order_ids' => [],
                'quantity_total' => 0,
                'total_amount' => 0,
                'weight_sum' => 0.0,
                'items_with_weight_count' => 0,
                'items_without_weight_count' => 0,
            ];
        }

        $hasDimensionFilter = $filters['region_id'] !== null || $filters['hub_id'] !== null;

        $rows = $this->basePaidOrderItemsQuery([
            'period' => self::PERIOD_ALL,
            'region_id' => $filters['region_id'],
            'hub_id' => $filters['hub_id'],
        ])
            ->whereBetween('orders.paid_at', [$startMonthWib->utc(), $endMonthWib->utc()])
            ->select([
                'order_items.order_id',
                'order_items.quantity',
                'order_items.subtotal',
                'order_items.weight_per_unit_snapshot',
                'orders.total as order_total',
                'orders.paid_at',
            ])
            ->get();

        foreach ($rows as $row) {
            if ($row->paid_at === null) {
                continue;
            }

            $monthKey = CarbonImmutable::parse($row->paid_at, 'UTC')
                ->setTimezone(self::TIMEZONE)
                ->format('Y-m');

            if (! array_key_exists($monthKey, $buckets)) {
                continue;
            }

            $orderId = (int) $row->order_id;
            $isNewOrderInBucket = ! isset($buckets[$monthKey]['order_ids'][$orderId]);

            if ($isNewOrderInBucket) {
                $buckets[$monthKey]['order_ids'][$orderId] = true;

                if (! $hasDimensionFilter) {
                    $buckets[$monthKey]['total_amount'] += (int) $row->order_total;
                }
            }

            if ($hasDimensionFilter) {
                $buckets[$monthKey]['total_amount'] += (int) $row->subtotal;
            }

            $buckets[$monthKey]['quantity_total'] += (int) $row->quantity;

            if ($row->weight_per_unit_snapshot !== null) {
                $buckets[$monthKey]['items_with_weight_count']++;
                $buckets[$monthKey]['weight_sum'] += (float) $row->weight_per_unit_snapshot * (int) $row->quantity;
            } else {
                $buckets[$monthKey]['items_without_weight_count']++;
            }
        }

        // Tangani pesanan lunas tanpa order_items (jika ada pada fixture minimal) saat tanpa filter wilayah/hub.
        if (! $hasDimensionFilter) {
            $ordersWithoutItems = Order::query()
                ->where('orders.status', Order::STATUS_PAID)
                ->whereBetween('orders.paid_at', [$startMonthWib->utc(), $endMonthWib->utc()])
                ->whereNotExists(function (QueryBuilder $sub) {
                    $sub->selectRaw('1')
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id');
                })
                ->select(['orders.id', 'orders.total', 'orders.paid_at'])
                ->get();

            foreach ($ordersWithoutItems as $order) {
                if ($order->paid_at === null) {
                    continue;
                }

                $monthKey = CarbonImmutable::instance($order->paid_at)
                    ->setTimezone(self::TIMEZONE)
                    ->format('Y-m');

                if (! array_key_exists($monthKey, $buckets)) {
                    continue;
                }

                $orderId = (int) $order->id;

                if (! isset($buckets[$monthKey]['order_ids'][$orderId])) {
                    $buckets[$monthKey]['order_ids'][$orderId] = true;
                    $buckets[$monthKey]['total_amount'] += (int) $order->total;
                }
            }
        }

        $counts = array_values(array_map(fn (array $bucket) => count($bucket['order_ids']), $buckets));
        $maxOrders = max(1, ...$counts);

        $series = [];
        foreach ($buckets as $bucket) {
            $ordersCount = count($bucket['order_ids']);
            $withWeight = $bucket['items_with_weight_count'];

            $series[] = [
                'month_key' => $bucket['month_key'],
                'label' => $bucket['label'],
                'orders_count' => $ordersCount,
                'quantity_total' => $bucket['quantity_total'],
                'total_amount' => $bucket['total_amount'],
                'known_weight_kg' => $withWeight > 0 ? round($bucket['weight_sum'], 2) : null,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => $bucket['items_without_weight_count'],
                'height_percent' => $ordersCount > 0
                    ? max(8, (int) round(($ordersCount / $maxOrders) * 100))
                    : 0,
            ];
        }

        return $series;
    }

    /**
     * Agregat transaksi lunas berdasarkan wilayah.
     *
     * Resolusi wilayah mengikuti prioritas: listings.region_id -> hubs.region_id -> sellers.region_id.
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     * @return list<array{
     *     region_id: int|null,
     *     region_name: string,
     *     orders_count: int,
     *     quantity_total: int,
     *     subtotal_amount: int,
     *     known_weight_kg: float|null,
     *     items_with_weight_count: int,
     *     items_without_weight_count: int
     * }>
     */
    public function aggregateByRegion(array $filters): array
    {
        $effectiveRegionExpr = $this->effectiveRegionIdExpression();

        $rows = $this->basePaidOrderItemsQuery($filters)
            ->leftJoin('regions as effective_regions', 'effective_regions.id', '=', DB::raw($effectiveRegionExpr))
            ->selectRaw("{$effectiveRegionExpr} as resolved_region_id")
            ->selectRaw('effective_regions.name as region_name')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_total')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as subtotal_amount')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN order_items.quantity * order_items.weight_per_unit_snapshot ELSE 0 END) as known_weight_sum')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN 1 ELSE 0 END) as with_weight_count')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NULL THEN 1 ELSE 0 END) as without_weight_count')
            ->groupByRaw("{$effectiveRegionExpr}, effective_regions.name")
            ->orderByDesc('orders_count')
            ->orderByDesc('subtotal_amount')
            ->orderBy('effective_regions.name')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $withWeight = (int) ($row->with_weight_count ?? 0);

            $result[] = [
                'region_id' => $row->resolved_region_id !== null ? (int) $row->resolved_region_id : null,
                'region_name' => $row->region_name !== null ? (string) $row->region_name : self::UNKNOWN_REGION_LABEL,
                'orders_count' => (int) ($row->orders_count ?? 0),
                'quantity_total' => (int) ($row->quantity_total ?? 0),
                'subtotal_amount' => (int) ($row->subtotal_amount ?? 0),
                'known_weight_kg' => $withWeight > 0 ? round((float) $row->known_weight_sum, 2) : null,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => (int) ($row->without_weight_count ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Agregat transaksi lunas berdasarkan kombinasi wilayah dan hub komunitas.
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     * @return list<array{
     *     region_id: int|null,
     *     region_name: string,
     *     hub_id: int|null,
     *     hub_name: string,
     *     orders_count: int,
     *     quantity_total: int,
     *     subtotal_amount: int,
     *     known_weight_kg: float|null,
     *     items_with_weight_count: int,
     *     items_without_weight_count: int
     * }>
     */
    public function aggregateByRegionAndHub(array $filters): array
    {
        $effectiveRegionExpr = $this->effectiveRegionIdExpression();

        $rows = $this->basePaidOrderItemsQuery($filters)
            ->leftJoin('regions as effective_regions', 'effective_regions.id', '=', DB::raw($effectiveRegionExpr))
            ->selectRaw("{$effectiveRegionExpr} as resolved_region_id")
            ->selectRaw('effective_regions.name as region_name')
            ->selectRaw('hubs.id as resolved_hub_id')
            ->selectRaw('hubs.name as hub_name')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_total')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as subtotal_amount')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN order_items.quantity * order_items.weight_per_unit_snapshot ELSE 0 END) as known_weight_sum')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NOT NULL THEN 1 ELSE 0 END) as with_weight_count')
            ->selectRaw('SUM(CASE WHEN order_items.weight_per_unit_snapshot IS NULL THEN 1 ELSE 0 END) as without_weight_count')
            ->groupByRaw("{$effectiveRegionExpr}, effective_regions.name, hubs.id, hubs.name")
            ->orderByDesc('orders_count')
            ->orderByDesc('subtotal_amount')
            ->orderBy('effective_regions.name')
            ->orderBy('hubs.name')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $withWeight = (int) ($row->with_weight_count ?? 0);

            $result[] = [
                'region_id' => $row->resolved_region_id !== null ? (int) $row->resolved_region_id : null,
                'region_name' => $row->region_name !== null ? (string) $row->region_name : self::UNKNOWN_REGION_LABEL,
                'hub_id' => $row->resolved_hub_id !== null ? (int) $row->resolved_hub_id : null,
                'hub_name' => $row->hub_name !== null ? (string) $row->hub_name : self::UNKNOWN_HUB_LABEL,
                'orders_count' => (int) ($row->orders_count ?? 0),
                'quantity_total' => (int) ($row->quantity_total ?? 0),
                'subtotal_amount' => (int) ($row->subtotal_amount ?? 0),
                'known_weight_kg' => $withWeight > 0 ? round((float) $row->known_weight_sum, 2) : null,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => (int) ($row->without_weight_count ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Netralkan nilai sel CSV agar tidak dieksekusi sebagai formula spreadsheet (FR-022 / NFR-009).
     *
     * - Nilai numerik (int/float) dipertahankan apa adanya.
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
     * Query dasar order_items dari pesanan berstatus PAID beserta relasi listing, hub, dan penjual.
     *
     * @param  array{period: string, region_id: int|null, hub_id: int|null}  $filters
     */
    protected function basePaidOrderItemsQuery(array $filters): QueryBuilder
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('listings', 'listings.id', '=', 'order_items.listing_id')
            ->leftJoin('hubs', 'hubs.id', '=', 'listings.hub_id')
            ->leftJoin('users as sellers', 'sellers.id', '=', 'orders.seller_id')
            ->where('orders.status', Order::STATUS_PAID);

        $this->applyPeriodFilter($query, $filters['period'], 'orders');

        if ($filters['region_id'] !== null) {
            $query->whereRaw($this->effectiveRegionIdExpression().' = ?', [$filters['region_id']]);
        }

        if ($filters['hub_id'] !== null) {
            $query->where('listings.hub_id', $filters['hub_id']);
        }

        return $query;
    }

    /**
     * Terapkan filter rentang tanggal lunas (paid_at) dalam UTC berbasis WIB.
     *
     * @param  Builder<Order>|QueryBuilder  $query
     */
    protected function applyPeriodFilter(Builder|QueryBuilder $query, string $period, string $ordersTable): void
    {
        $range = $this->periodRange($period);

        if ($range !== null) {
            $query->whereBetween("{$ordersTable}.paid_at", $range);
        }
    }

    /**
     * Ekspresi SQL resolusi wilayah transaksi: wilayah listing -> wilayah hub -> wilayah penjual.
     */
    protected function effectiveRegionIdExpression(): string
    {
        return 'COALESCE(listings.region_id, hubs.region_id, sellers.region_id)';
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
