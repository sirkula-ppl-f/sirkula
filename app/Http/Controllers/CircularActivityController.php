<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Services\CircularActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman catatan aktivitas sirkular pribadi warga (PBI-20 / FR-022).
 *
 * Hanya baca. Data selalu diambil dari user yang sedang login, sehingga
 * warga tidak bisa melihat aktivitas warga lain.
 */
class CircularActivityController extends Controller
{
    use ListsMasterData;

    public function __construct(private CircularActivityService $activity) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Nilai non-skalar (mis. array dari URL) atau nilai tidak dikenal otomatis kembali ke 'all'.
        $period = $this->activity->normalizePeriod($this->queryText($request, 'periode'));
        $role = $this->activity->normalizeRole($this->queryText($request, 'peran'));

        $items = $this->activity->paginateItems($user, $period, $role);

        if ($redirect = $this->redirectIfPageEmpty($request, $items)) {
            return $redirect;
        }

        $report = $this->activity->summarize($user, $period, $role);
        $monthlySeries = $this->activity->monthlySeries($user, CircularActivityService::DEFAULT_MONTHS, $role);

        return view('circular-activity.index', [
            'summary' => $report['summary'],
            'byType' => $report['by_type'],
            'filters' => $report['filters'],
            'monthlySeries' => $monthlySeries,
            'items' => $items,
            'periodOptions' => $this->activity->periodOptions(),
            'roleOptions' => $this->activity->roleOptions(),
        ]);
    }
}
