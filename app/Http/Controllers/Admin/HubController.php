<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $hubs = Hub::with('region')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $validated['status'] = 'ACTIVE';

        $hub = Hub::create($validated);

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Hub berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hub $hub): View
    {
        $hub->load([
            'region',
            'managers.user',
        ]);

        return view('admin.hubs.show', compact('hub'));
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
    public function update(Request $request, Hub $hub): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $hub->update($validated);

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Hub berhasil diperbarui.');
    }

    /**
     * Archive the specified resource without deleting it.
     */
    public function archive(Hub $hub): RedirectResponse
    {
        DB::transaction(function () use ($hub) {
            $hub->update([
                'status' => 'ARCHIVED',
            ]);

            $hub->managers()
                ->where('assignment_status', 'ACTIVE')
                ->update([
                    'assignment_status' => 'INACTIVE',
                    'deactivated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.hubs.index')
            ->with('success', 'Hub berhasil diarsipkan.');
    }

    /**
     * Validation rules for creating and updating hubs.
     */
    private function validationRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'required',
                Rule::in([
                    'BANK_SAMPAH',
                    'RW',
                    'KOMUNITAS',
                ]),
            ],

            'region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id'),
            ],

            'public_address' => [
                'required',
                'string',
                'max:2000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
