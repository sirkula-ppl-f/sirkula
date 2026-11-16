<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferencePriceStatus;
use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReferencePriceRequest;
use App\Services\ReferencePriceService;
use App\Support\IndonesianFormat;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin menerbitkan harga acuan dan melihat riwayatnya.
 *
 * Riwayat append-only: sengaja tidak ada edit, update, atau destroy.
 */
class ReferencePriceController extends Controller
{
    use ListsMasterData;

    public function __construct(private ReferencePriceService $prices) {}

    public function index(Request $request): View|RedirectResponse
    {
        $filters = [
            'material' => $this->queryId($request, 'material'),
            'provinsi' => $this->queryId($request, 'provinsi'),
            'dari' => $this->queryDate($request, 'dari'),
            'sampai' => $this->queryDate($request, 'sampai'),
        ];

        $prices = $this->prices->paginateHistory([
            'material' => $filters['material'],
            'region' => $filters['provinsi'],
            'from' => $filters['dari'],
            'until' => $filters['sampai'],
        ]);

        return $this->redirectIfPageEmpty($request, $prices) ?? view('admin.reference-prices.index', [
            'prices' => $prices,
            'filters' => $filters,
            'materials' => $this->prices->materialFilterOptions(),
            'provinces' => $this->prices->provinceOptions(),
            'today' => $this->prices->today(),
        ]);
    }

    /**
     * Bila form dikirim ulang karena validasi gagal, tampilkan harga yang sedang berlaku untuk
     * material + provinsi yang tadi dipilih (dirender server, tanpa AJAX).
     */
    public function create(Request $request): View
    {
        return view('admin.reference-prices.create', [
            'materials' => $this->prices->activeMaterialOptions(),
            'provinces' => $this->prices->provinceOptions(),
            'selection' => $this->prices->currentForSelection($request->old('material_id'), $request->old('region_id')),
            'today' => $this->prices->today(),
            'minDate' => CarbonImmutable::parse(ReferencePriceService::MIN_EFFECTIVE_DATE),
            'maxDate' => $this->prices->maxEffectiveDate(),
        ]);
    }

    public function store(StoreReferencePriceRequest $request): RedirectResponse
    {
        $price = $this->prices->publish($request->validated(), $request->user());
        $price->load(['material', 'region']);

        $key = $price->effective_date->greaterThan($this->prices->today())
            ? ReferencePriceStatus::Scheduled->value
            : 'published';

        return redirect()->route('admin.reference-prices.index')
            ->with('status', __("reference-prices.flash.{$key}", [
                'material' => $price->material->name,
                'region' => $price->region->name,
                'price' => IndonesianFormat::pricePerKg($price->price_per_kg),
                'date' => IndonesianFormat::date($price->effective_date),
            ]));
    }

    /**
     * Id dari query string. Selain bilangan bulat positif dianggap tidak diisi.
     */
    private function queryId(Request $request, string $key): ?int
    {
        $value = $this->queryText($request, $key);

        return $value !== null && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Tanggal 'Y-m-d' dari query string. Tanggal yang tidak valid dianggap tidak diisi.
     */
    private function queryDate(Request $request, string $key): ?string
    {
        $value = $this->queryText($request, $key);

        if ($value === null) {
            return null;
        }

        // Tanggal yang meluap (misalnya 2026-13-45) ikut ditolak karena hasil format-nya berbeda.
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
