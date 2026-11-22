<?php

namespace App\Services;

use App\Enums\ListingType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Perhitungan catatan aktivitas sirkular pribadi warga (PBI-20 / FR-022).
 *
 * Seluruh angka berasal dari pesanan berstatus PAID (Lunas) dan hanya bersifat
 * estimasi dari data transaksi (tanpa klaim pengukuran fisik).
 */
class CircularActivityService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const DEFAULT_MONTHS = 6;

    public const ITEMS_PER_PAGE = 10;

    public const PERIOD_ALL = 'all';

    public const PERIOD_THIS_MONTH = 'this_month';

    public const PERIOD_LAST_3_MONTHS = 'last_3_months';

    public const PERIOD_THIS_YEAR = 'this_year';

    public const ROLE_ALL = 'all';

    public const ROLE_SELLER = 'seller';

    public const ROLE_BUYER = 'buyer';

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
     * Daftar opsi filter peran transaksi yang didukung.
     *
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        return [
            self::ROLE_ALL => 'Semua Aktivitas (Penjual & Pembeli)',
            self::ROLE_SELLER => 'Sebagai Penjual / Penyalur',
            self::ROLE_BUYER => 'Sebagai Pembeli',
        ];
    }

    public function normalizePeriod(?string $period): string
    {
        return array_key_exists((string) $period, $this->periodOptions())
            ? (string) $period
            : self::PERIOD_ALL;
    }

    public function normalizeRole(?string $role): string
    {
        return array_key_exists((string) $role, $this->roleOptions())
            ? (string) $role
            : self::ROLE_ALL;
    }

    /**
     * Ringkasan transaksi lunas dan estimasi berat material milik warga (FR-022).
     *
     * @return array{
     *     filters: array{period: string, role: string},
     *     summary: array{
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
     *     }>
     * }
     */
    public function summarize(User $user, ?string $period = null, ?string $role = null): array
    {
        $period = $this->normalizePeriod($period);
        $role = $this->normalizeRole($role);

        $orderQuery = $this->basePaidOrdersQuery($user, $period, $role);

        // Hitung di level pesanan terlebih dahulu agar 1 pesanan dengan banyak item tidak terhitung ganda.
        $orderAggregate = (clone $orderQuery)
            ->selectRaw('COUNT(*) as total_paid_orders, COALESCE(SUM(total), 0) as total_amount')
            ->first();

        $totalPaidOrders = (int) ($orderAggregate->total_paid_orders ?? 0);
        $totalAmount = (int) ($orderAggregate->total_amount ?? 0);

        $itemRows = OrderItem::query()
            ->whereIn('order_id', (clone $orderQuery)->select('id'))
            ->selectRaw('type_snapshot')
            ->selectRaw('COUNT(DISTINCT order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity_total')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal_amount')
            ->selectRaw('SUM(CASE WHEN weight_per_unit_snapshot IS NOT NULL THEN quantity * weight_per_unit_snapshot ELSE 0 END) as known_weight_sum')
            ->selectRaw('SUM(CASE WHEN weight_per_unit_snapshot IS NOT NULL THEN 1 ELSE 0 END) as with_weight_count')
            ->selectRaw('SUM(CASE WHEN weight_per_unit_snapshot IS NULL THEN 1 ELSE 0 END) as without_weight_count')
            ->groupBy('type_snapshot')
            ->get()
            ->keyBy(fn (OrderItem $row) => $row->type_snapshot instanceof ListingType
                ? $row->type_snapshot->value
                : (string) $row->type_snapshot);

        $byType = [];
        $totalItemsQuantity = 0;
        $totalKnownWeight = 0.0;
        $totalWithWeight = 0;
        $totalWithoutWeight = 0;

        foreach (ListingType::cases() as $listingType) {
            $row = $itemRows->get($listingType->value);

            $withWeight = (int) ($row?->with_weight_count ?? 0);
            $withoutWeight = (int) ($row?->without_weight_count ?? 0);
            $quantityTotal = (int) ($row?->quantity_total ?? 0);
            $knownWeightKg = $withWeight > 0
                ? round((float) $row->known_weight_sum, 2)
                : null;

            $byType[$listingType->value] = [
                'type' => $listingType,
                'label' => $listingType->label(),
                'orders_count' => (int) ($row?->orders_count ?? 0),
                'quantity_total' => $quantityTotal,
                'subtotal_amount' => (int) ($row?->subtotal_amount ?? 0),
                'known_weight_kg' => $knownWeightKg,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => $withoutWeight,
            ];

            $totalItemsQuantity += $quantityTotal;
            $totalWithWeight += $withWeight;
            $totalWithoutWeight += $withoutWeight;

            if ($knownWeightKg !== null) {
                $totalKnownWeight += $knownWeightKg;
            }
        }

        return [
            'filters' => [
                'period' => $period,
                'role' => $role,
            ],
            'summary' => [
                'total_paid_orders' => $totalPaidOrders,
                'total_items_quantity' => $totalItemsQuantity,
                'total_amount' => $totalAmount,
                // Berat yang tidak diketahui tidak dianggap 0 kg (FR-022).
                'known_weight_kg' => $totalWithWeight > 0 ? round($totalKnownWeight, 2) : null,
                'items_with_weight_count' => $totalWithWeight,
                'items_without_weight_count' => $totalWithoutWeight,
            ],
            'by_type' => $byType,
        ];
    }

    /**
     * Deret grafik aktivitas bulanan dalam N bulan terakhir (urut lama ke baru, zona waktu WIB).
     *
     * Pengelompokan bulan dilakukan di PHP berdasarkan paid_at dalam WIB agar konsisten di MySQL
     * maupun SQLite tanpa ketergantungan tabel timezone database.
     *
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
    public function monthlySeries(User $user, int $months = self::DEFAULT_MONTHS, ?string $role = null): array
    {
        $months = max(1, min(12, $months));
        $role = $this->normalizeRole($role);

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
                'orders_count' => 0,
                'quantity_total' => 0,
                'total_amount' => 0,
                'weight_sum' => 0.0,
                'items_with_weight_count' => 0,
                'items_without_weight_count' => 0,
            ];
        }

        $orders = $this->basePaidOrdersQuery($user, self::PERIOD_ALL, $role)
            ->whereBetween('paid_at', [$startMonthWib->utc(), $endMonthWib->utc()])
            ->select(['id', 'buyer_id', 'seller_id', 'total', 'paid_at'])
            ->with(['items:id,order_id,quantity,weight_per_unit_snapshot'])
            ->get();

        foreach ($orders as $order) {
            if ($order->paid_at === null) {
                continue;
            }

            $monthKey = CarbonImmutable::instance($order->paid_at)
                ->setTimezone(self::TIMEZONE)
                ->format('Y-m');

            if (! array_key_exists($monthKey, $buckets)) {
                continue;
            }

            $buckets[$monthKey]['orders_count']++;
            $buckets[$monthKey]['total_amount'] += (int) $order->total;

            foreach ($order->items as $item) {
                $buckets[$monthKey]['quantity_total'] += (int) $item->quantity;

                if ($item->weight_per_unit_snapshot !== null) {
                    $buckets[$monthKey]['items_with_weight_count']++;
                    $buckets[$monthKey]['weight_sum'] += (float) $item->weight_per_unit_snapshot * $item->quantity;
                } else {
                    $buckets[$monthKey]['items_without_weight_count']++;
                }
            }
        }

        $maxOrders = max(1, ...array_column($buckets, 'orders_count'));

        $series = [];
        foreach ($buckets as $bucket) {
            $withWeight = $bucket['items_with_weight_count'];

            $series[] = [
                'month_key' => $bucket['month_key'],
                'label' => $bucket['label'],
                'orders_count' => $bucket['orders_count'],
                'quantity_total' => $bucket['quantity_total'],
                'total_amount' => $bucket['total_amount'],
                'known_weight_kg' => $withWeight > 0 ? round($bucket['weight_sum'], 2) : null,
                'items_with_weight_count' => $withWeight,
                'items_without_weight_count' => $bucket['items_without_weight_count'],
                'height_percent' => $bucket['orders_count'] > 0
                    ? max(8, (int) round(($bucket['orders_count'] / $maxOrders) * 100))
                    : 0,
            ];
        }

        return $series;
    }

    /**
     * Daftar riwayat item dari pesanan lunas milik warga (terbaru di atas).
     *
     * @return LengthAwarePaginator<int, OrderItem>
     */
    public function paginateItems(
        User $user,
        ?string $period = null,
        ?string $role = null,
        int $perPage = self::ITEMS_PER_PAGE
    ): LengthAwarePaginator {
        $period = $this->normalizePeriod($period);
        $role = $this->normalizeRole($role);
        $perPage = max(1, min(50, $perPage));

        $query = OrderItem::query()
            ->select('order_items.*')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->with(['order:id,buyer_id,seller_id,order_number,status,paid_at']);

        $this->applyPaidOrderConstraints($query, $user, $period, $role, 'orders');

        return $query->orderByDesc('orders.paid_at')
            ->orderByDesc('order_items.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Query dasar pesanan lunas (PAID) milik warga sesuai filter peran dan periode.
     *
     * @return Builder<Order>
     */
    protected function basePaidOrdersQuery(User $user, string $period, string $role): Builder
    {
        $query = Order::query();
        $this->applyPaidOrderConstraints($query, $user, $period, $role, 'orders');

        return $query;
    }

    /**
     * Terapkan syarat status PAID, kepemilikan peran, dan rentang tanggal bayar secara konsisten.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyPaidOrderConstraints(
        Builder $query,
        User $user,
        string $period,
        string $role,
        string $ordersTable
    ): void {
        $userId = $user->getKey();

        $query->where("{$ordersTable}.status", Order::STATUS_PAID);

        if ($role === self::ROLE_SELLER) {
            $query->where("{$ordersTable}.seller_id", $userId);
        } elseif ($role === self::ROLE_BUYER) {
            $query->where("{$ordersTable}.buyer_id", $userId);
        } else {
            $query->where(function (Builder $q) use ($ordersTable, $userId) {
                $q->where("{$ordersTable}.buyer_id", $userId)
                    ->orWhere("{$ordersTable}.seller_id", $userId);
            });
        }

        $range = $this->periodRange($period);

        if ($range !== null) {
            $query->whereBetween("{$ordersTable}.paid_at", $range);
        }
    }

    /**
     * Rentang waktu UTC berdasarkan batas hari/bulan di WIB (Asia/Jakarta).
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
}
