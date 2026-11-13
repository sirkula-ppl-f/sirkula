<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignHubManagerRequest;
use App\Models\Hub;
use App\Models\HubManager;
use App\Services\HubService;
use Illuminate\Http\RedirectResponse;

class HubManagerController extends Controller
{
    public function __construct(
        private HubService $hubService
    ) {}

    /**
     * Menugaskan pengguna sebagai pengelola Hub.
     */
    public function store(
        AssignHubManagerRequest $request,
        Hub $hub
    ): RedirectResponse {
        $this->hubService->assignManager(
            $hub,
            (int) $request->validated('user_id')
        );

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Pengelola Hub berhasil ditugaskan.');
    }

    /**
     * Menonaktifkan penugasan pengelola Hub.
     */
    public function deactivate(
        Hub $hub,
        HubManager $manager
    ): RedirectResponse {
        $deactivated = $this->hubService->deactivateManager(
            $hub,
            $manager
        );

        if (! $deactivated) {
            return redirect()
                ->route('admin.hubs.show', $hub)
                ->with('info', 'Penugasan sudah tidak aktif.');
        }

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Penugasan pengelola berhasil dinonaktifkan.');
    }
}
