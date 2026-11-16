<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Models\Material;
use App\Models\Region;
use App\Services\ReferencePriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik harga acuan (PBI-05, FR-008): harga terbaru, riwayat, dan tren per material + provinsi.
 *
 * Hanya baca. Hanya material aktif, region provinsi, dan entri yang sudah berlaku hari ini (WIB);
 * entri terjadwal dan nama penerbit tidak pernah ditampilkan.
 */
class ReferencePriceController extends Controller
{
    use ListsMasterData;

    /**
     * Query string halaman daftar yang boleh dibawa ke halaman detail lewat parameter "kembali".
     */
    private const BACK_KEYS = ['material', 'provinsi', 'page'];

    public function __construct(private ReferencePriceService $prices) {}

    public function index(Request $request): View|RedirectResponse
    {
        $materials = $this->prices->activeMaterialOptions();
        $provinces = $this->prices->provinceOptions();

        // Nilai yang bukan pilihan di form (misalnya material nonaktif) diabaikan, jadi filter yang
        // dipakai selalu sama dengan yang terlihat terpilih.
        $filters = [
            'material' => $this->optionId($request, 'material', $materials),
            'provinsi' => $this->optionId($request, 'provinsi', $provinces),
        ];

        $prices = $this->prices->paginateLatest([
            'material' => $filters['material'],
            'region' => $filters['provinsi'],
        ]);

        return $this->redirectIfPageEmpty($request, $prices) ?? view('reference-prices.index', [
            'prices' => $prices,
            'filters' => $filters,
            'materials' => $materials,
            'provinces' => $provinces,
            'back' => $this->backParameter([...$filters, 'page' => $prices->currentPage()]),
        ]);
    }

    /**
     * 404 bila material nonaktif, region bukan provinsi, atau pasangan ini belum punya harga yang berlaku.
     */
    public function show(Request $request, Material $material, Region $region): View|RedirectResponse
    {
        $latest = $this->prices->publicLatestFor($material, $region);

        abort_if($latest === null, 404);

        $history = $this->prices->paginatePairHistory($material, $region);

        return $this->redirectIfPageEmpty($request, $history) ?? view('reference-prices.show', [
            'material' => $material,
            'region' => $region,
            'latest' => $latest,
            'history' => $history,
            'trend' => $this->prices->trendFor($material, $region),
            'today' => $this->prices->today(),
            'backUrl' => route('reference-prices.index', $this->backQuery($request)),
        ]);
    }

    /**
     * Id dari query string yang ada di daftar pilihan. Selain itu dianggap tidak diisi.
     *
     * @param  array<int, string>  $options
     */
    private function optionId(Request $request, string $key, array $options): ?int
    {
        $id = $this->positiveInt($this->queryText($request, $key));

        return $id !== null && array_key_exists($id, $options) ? $id : null;
    }

    /**
     * Nilai parameter "kembali" untuk tautan detail: filter dan halaman daftar saat ini, atau null.
     *
     * @param  array<string, ?int>  $state
     */
    private function backParameter(array $state): ?string
    {
        $query = array_filter($state, fn (?int $value, string $key) => $value !== null && ! ($key === 'page' && $value === 1), ARRAY_FILTER_USE_BOTH);

        return $query === [] ? null : http_build_query($query);
    }

    /**
     * Query string daftar dari parameter "kembali". Hanya key BACK_KEYS berisi bilangan bulat positif
     * yang dipakai, dan URL dibangun ulang dari nama route sehingga tidak bisa mengarah ke luar situs.
     *
     * @return array<string, int>
     */
    private function backQuery(Request $request): array
    {
        $raw = $this->queryText($request, 'kembali');

        if ($raw === null) {
            return [];
        }

        parse_str($raw, $values);

        $query = [];

        foreach (self::BACK_KEYS as $key) {
            $value = $this->positiveInt($values[$key] ?? null);

            if ($value !== null && ! ($key === 'page' && $value === 1)) {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    /**
     * Bilangan bulat positif dari teks berisi angka saja, selain itu null.
     */
    private function positiveInt(mixed $value): ?int
    {
        return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }
}
