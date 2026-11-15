<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use App\Support\TrendChart;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Harga acuan material per provinsi (PBI-05, FR-008).
 *
 * Satu-satunya titik penentu "hari ini" (WIB) untuk harga acuan. config/app.php tetap UTC.
 * Harga acuan bersifat informatif: service ini tidak pernah menulis ke tabel listings.
 */
class ReferencePriceService
{
    /**
     * Zona waktu untuk tanggal berlaku, label Berlaku/Terjadwal, dan tampilan waktu terbit.
     */
    public const TIMEZONE = 'Asia/Jakarta';

    public const PER_PAGE = 15;

    /**
     * Tanggal berlaku paling awal yang boleh diterbitkan.
     */
    public const MIN_EFFECTIVE_DATE = '2020-01-01';

    /**
     * Tanggal berlaku paling lambat: hari ini (WIB) ditambah sekian hari.
     */
    public const MAX_DAYS_AHEAD = 90;

    /**
     * Harga per kg paling besar (rupiah).
     */
    public const MAX_PRICE = 10_000_000;

    /**
     * Batas naik ke wilayah induk saat mencari provinsi, untuk berjaga dari data parent_id yang berputar.
     */
    private const MAX_REGION_DEPTH = 5;

    /**
     * Awal hari ini di WIB.
     */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Tanggal berlaku paling lambat yang boleh diterbitkan hari ini.
     */
    public function maxEffectiveDate(): CarbonImmutable
    {
        return $this->today()->addDays(self::MAX_DAYS_AHEAD);
    }

    /**
     * Terbitkan harga acuan baru. Data sudah divalidasi FormRequest; published_by diisi dari $admin.
     *
     * @param  array{material_id: int|string, region_id: int|string, price_per_kg: int|string, effective_date: string, source: string}  $data
     *
     * @throws AuthorizationException bila $admin bukan admin
     */
    public function publish(array $data, User $admin): ReferencePrice
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException(__('reference-prices.publish_forbidden'));
        }

        return ReferencePrice::create([
            'material_id' => (int) $data['material_id'],
            'region_id' => (int) $data['region_id'],
            'price_per_kg' => (int) $data['price_per_kg'],
            'effective_date' => $data['effective_date'],
            'source' => trim($data['source']),
            'published_by' => $admin->id,
        ]);
    }

    /**
     * Riwayat semua entri untuk halaman admin, terbaru di atas, termasuk entri terjadwal dan
     * entri material nonaktif. Atribut current_id ikut dimuat untuk label Berlaku/Riwayat.
     *
     * @param  array{material?: ?int, region?: ?int, from?: ?string, until?: ?string}  $filters  tanggal 'Y-m-d'
     * @return LengthAwarePaginator<int, ReferencePrice>
     */
    public function paginateHistory(array $filters = []): LengthAwarePaginator
    {
        $query = ReferencePrice::query()
            ->with(['material', 'region', 'publisher'])
            ->withCurrentId($this->today()->toDateString());

        if (isset($filters['material'])) {
            $query->where($query->qualifyColumn('material_id'), $filters['material']);
        }

        if (isset($filters['region'])) {
            $query->where($query->qualifyColumn('region_id'), $filters['region']);
        }

        if (isset($filters['from'])) {
            $query->where($query->qualifyColumn('effective_date'), '>=', $filters['from']);
        }

        if (isset($filters['until'])) {
            $query->where($query->qualifyColumn('effective_date'), '<=', $filters['until']);
        }

        return $query->newestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Harga terbaru per pasangan material + provinsi untuk halaman publik, urut nama material lalu
     * nama provinsi. Hanya material aktif, region provinsi, dan entri yang sudah berlaku hari ini (WIB).
     * Relasi material dan region dimuat sekaligus, jadi jumlah query tidak bergantung pada jumlah baris.
     *
     * @param  array{material?: ?int, region?: ?int}  $filters
     * @return LengthAwarePaginator<int, ReferencePrice>
     */
    public function paginateLatest(array $filters = []): LengthAwarePaginator
    {
        $table = (new ReferencePrice)->getTable();

        $query = ReferencePrice::query()
            ->select($table.'.*')
            ->join('materials', 'materials.id', '=', $table.'.material_id')
            ->join('regions', 'regions.id', '=', $table.'.region_id')
            ->where('materials.is_active', true)
            ->whereNull('regions.parent_id')
            ->latestPerPair($this->today()->toDateString())
            ->with(['material', 'region']);

        if (isset($filters['material'])) {
            $query->where($table.'.material_id', $filters['material']);
        }

        if (isset($filters['region'])) {
            $query->where($table.'.region_id', $filters['region']);
        }

        return $query->orderBy('materials.name')
            ->orderBy('materials.id')
            ->orderBy('regions.name')
            ->orderBy('regions.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Harga terbaru untuk halaman publik. null bila material nonaktif, region bukan provinsi,
     * atau pasangan ini belum punya entri yang sudah berlaku (entri terjadwal tidak dihitung).
     */
    public function publicLatestFor(Material $material, Region $region): ?ReferencePrice
    {
        if (! $material->is_active || $region->parent_id !== null) {
            return null;
        }

        return $this->latestFor($material, $region);
    }

    /**
     * Riwayat publik satu pasangan material + provinsi: semua entri yang sudah berlaku (termasuk koreksi
     * bertanggal sama), terbaru di atas. Atribut current_id ikut dimuat untuk label Berlaku/Riwayat.
     *
     * @return LengthAwarePaginator<int, ReferencePrice>
     */
    public function paginatePairHistory(Material $material, Region $region): LengthAwarePaginator
    {
        $today = $this->today()->toDateString();
        $query = ReferencePrice::query();

        return $query->where($query->qualifyColumn('material_id'), $material->getKey())
            ->where($query->qualifyColumn('region_id'), $region->getKey())
            ->effectiveOn($today)
            ->withCurrentId($today)
            ->newestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Data grafik tren satu pasangan: satu entri per tanggal berlaku (id terbesar di tanggal itu, sama
     * dengan aturan harga terbaru), paling banyak TrendChart::MAX_POINTS tanggal terakhir yang sudah
     * berlaku, urut kronologis (lama ke baru).
     *
     * @return EloquentCollection<int, ReferencePrice>
     */
    public function trendFor(Material $material, Region $region): EloquentCollection
    {
        $table = (new ReferencePrice)->getTable();
        $today = $this->today()->toDateString();

        return ReferencePrice::query()
            ->whereIn('id', fn (QueryBuilder $latestPerDate) => $latestPerDate->from($table)
                ->selectRaw('max(id)')
                ->where('material_id', $material->getKey())
                ->where('region_id', $region->getKey())
                ->where('effective_date', '<=', $today)
                ->groupBy('effective_date'))
            ->orderByDesc('effective_date')
            ->limit(TrendChart::MAX_POINTS)
            ->get(['id', 'price_per_kg', 'effective_date'])
            ->reverse()
            ->values();
    }

    /**
     * Pilihan provinsi (region dengan parent_id null), urut nama.
     *
     * @return array<int, string> id => nama
     */
    public function provinceOptions(): array
    {
        return Region::query()->whereNull('parent_id')->orderBy('name')->orderBy('id')->pluck('name', 'id')->all();
    }

    /**
     * Pilihan material untuk form terbitkan: hanya material aktif.
     *
     * @return array<int, string> id => nama
     */
    public function activeMaterialOptions(): array
    {
        return Material::active()->orderBy('name')->orderBy('id')->pluck('name', 'id')->all();
    }

    /**
     * Pilihan material untuk filter riwayat admin: semua material, yang nonaktif diberi tanda.
     *
     * @return array<int, string> id => nama
     */
    public function materialFilterOptions(): array
    {
        return Material::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'is_active'])
            ->mapWithKeys(fn (Material $material) => [
                $material->id => $material->is_active
                    ? $material->name
                    : __('reference-prices.admin.inactive_material_option', ['name' => $material->name]),
            ])
            ->all();
    }

    /**
     * Harga yang sedang berlaku untuk pilihan di form terbitkan (misalnya old input setelah validasi gagal).
     * null bila material atau provinsi tidak dipilih atau tidak valid.
     *
     * @return array{material: Material, region: Region, price: ?ReferencePrice}|null
     */
    public function currentForSelection(mixed $materialId, mixed $regionId): ?array
    {
        if (! $this->isId($materialId) || ! $this->isId($regionId)) {
            return null;
        }

        $material = Material::query()->find((int) $materialId);
        $region = Region::query()->whereNull('parent_id')->find((int) $regionId);

        if ($material === null || $region === null) {
            return null;
        }

        return [
            'material' => $material,
            'region' => $region,
            'price' => $this->latestFor($material, $region),
        ];
    }

    /**
     * Harga acuan terbaru untuk material di provinsi $region pada saat $on (bawaan: sekarang).
     *
     * - "Terbaru": effective_date <= tanggal $on di WIB, urut effective_date terbaru lalu id terbesar.
     * - $region yang bukan provinsi (kab/kota) dinaikkan ke provinsi induknya lewat parent_id.
     * - Material nonaktif tidak disaring; pemanggil yang memutuskan perlu ditampilkan atau tidak.
     * - null bila belum ada harga yang berlaku.
     */
    public function latestFor(Material $material, Region $region, ?CarbonInterface $on = null): ?ReferencePrice
    {
        $province = $this->provinceOf($region);

        if ($province === null) {
            return null;
        }

        $date = $on === null ? $this->today() : CarbonImmutable::instance($on)->setTimezone(self::TIMEZONE);

        return ReferencePrice::query()
            ->where('material_id', $material->getKey())
            ->where('region_id', $province->getKey())
            ->effectiveOn($date->toDateString())
            ->newestFirst()
            ->first();
    }

    /**
     * Provinsi (region dengan parent_id null) untuk $region. null bila rantai parent_id rusak.
     */
    public function provinceOf(Region $region): ?Region
    {
        $current = $region;

        for ($depth = 0; $current->parent_id !== null; $depth++) {
            if ($depth >= self::MAX_REGION_DEPTH || $current->parent === null) {
                return null;
            }

            $current = $current->parent;
        }

        return $current;
    }

    /**
     * Bilangan bulat positif (int, atau string berisi angka saja).
     */
    private function isId(mixed $value): bool
    {
        return (is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0);
    }
}
