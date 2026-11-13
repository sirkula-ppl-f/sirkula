<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHubRequest;
use App\Http\Requests\Admin\UpdateHubRequest;
use App\Models\Hub;
use App\Models\Region;
use App\Models\User;
use App\Services\HubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubController extends Controller
{
    public function __construct(
        private HubService $hubService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $rawSearch = $request->query('search');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) ? $rawStatus : '';

        $hubs = $this->hubService->paginate([
            'search' => $search,
            'status' => $status,
        ]);

        return view('admin.hubs.index', compact('hubs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $regions = Region::orderBy('name')->get();

        return view('admin.hubs.create', compact('regions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHubRequest $request): RedirectResponse
    {
        $hub = $this->hubService->create(
            $request->validated()
        );

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', __('hub.flash.created', [], 'id'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Hub $hub): View
    {
        $hub->load([
            'region',
            'managers' => fn ($query) => $query
                ->with('user')
                ->orderByDesc('assigned_at')
                ->orderByDesc('id'),
        ]);

        $eligibleUsers = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereDoesntHave('hubAssignments', function ($query) use ($hub) {
                $query
                    ->where('hub_id', $hub->id)
                    ->where('assignment_status', 'ACTIVE');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.hubs.show', compact(
            'hub',
            'eligibleUsers'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hub $hub): View
    {
        $regions = Region::orderBy('name')->get();

        return view('admin.hubs.edit', compact('hub', 'regions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateHubRequest $request,
        Hub $hub
    ): RedirectResponse {
        $this->hubService->update(
            $hub,
            $request->validated()
        );

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', __('hub.flash.updated', [], 'id'));
    }

    /**
     * Archive the specified resource without deleting it.
     */
    public function archive(Hub $hub): RedirectResponse
    {
        $this->hubService->archive($hub);

        return redirect()
            ->route('admin.hubs.index')
            ->with('success', __('hub.flash.archived', [], 'id'));
    }
}
