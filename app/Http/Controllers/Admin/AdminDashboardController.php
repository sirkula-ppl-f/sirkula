<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Hub;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\User;
use App\Services\AdminMonitoringService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    use ListsMasterData;

    public function __construct(private AdminMonitoringService $monitoring) {}

    public function __invoke(Request $request): View
    {
        $filters = $this->monitoring->normalizeFilters([
            'periode' => $this->queryText($request, 'periode'),
            'region_id' => $this->queryText($request, 'region_id'),
            'hub_id' => $this->queryText($request, 'hub_id'),
        ]);

        $stats = [
            'categories_active' => Category::active()->count(),
            'materials_active' => Material::active()->count(),
            'hubs_active' => Hub::active()->count(),
            'reference_prices_total' => ReferencePrice::count(),
            'guides_published' => Guide::published()->count(),
            'users_total' => User::count(),
            'users_suspended' => User::where('status', UserStatus::Suspended)->count(),
        ];

        $monitoringData = $this->monitoring->getDashboardData($filters);

        return view('admin.dashboard', [
            'stats' => $stats,
            'monitoring' => $monitoringData,
            'filters' => $monitoringData['filters'],
            'periodOptions' => $this->monitoring->periodOptions(),
            'regionOptions' => $this->monitoring->regionOptions(),
            'hubOptions' => $this->monitoring->hubOptions(),
        ]);
    }

    /**
     * Ekspor laporan monitoring aktivitas platform ke CSV (PBI-21 / FR-022).
     * Hanya dapat diakses oleh Admin aktif.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->monitoring->normalizeFilters([
            'periode' => $this->queryText($request, 'periode'),
            'region_id' => $this->queryText($request, 'region_id'),
            'hub_id' => $this->queryText($request, 'hub_id'),
        ]);

        $dateStamp = CarbonImmutable::now(AdminMonitoringService::TIMEZONE)->format('Y-m-d');
        $filename = "laporan-monitoring-sirkula-{$dateStamp}.csv";

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, AdminMonitoringService::sanitizeCsvRow([
                'Bagian',
                'Periode Filter',
            ]));

            fputcsv($handle, AdminMonitoringService::sanitizeCsvRow([
                'Filter Aktif',
                $this->monitoring->periodOptions()[$filters['period']] ?? 'Semua Waktu',
            ]));

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
