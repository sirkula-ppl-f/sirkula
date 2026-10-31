<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMaterialRequest;
use App\Http\Requests\Admin\UpdateMaterialRequest;
use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    use ListsMasterData;

    public function __construct(private MaterialService $materials) {}

    public function index(Request $request): View|RedirectResponse
    {
        $filters = [
            'search' => $this->queryText($request, 'cari'),
            'status' => $this->statusFilter($request),
        ];

        $materials = $this->materials->paginate([
            'search' => $filters['search'],
            'active' => $filters['status'] === null ? null : $filters['status'] === 'aktif',
        ]);

        return $this->redirectIfPageEmpty($request, $materials) ?? view('admin.materials.index', [
            'materials' => $materials,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.materials.create');
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $material = $this->materials->create($request->validated());

        return redirect()->route('admin.materials.index')
            ->with('status', __('master-data.materials.created', ['name' => $material->name]));
    }

    public function edit(Material $material): View
    {
        return view('admin.materials.edit', [
            'material' => $material,
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        $this->materials->update($material, $request->validated());

        return redirect()->route('admin.materials.index')
            ->with('status', __('master-data.materials.updated', ['name' => $material->name]));
    }

    /**
     * Material yang sudah dipakai ditolak lewat MasterDataInUseException (flash error, bukan 500).
     */
    public function destroy(Material $material): RedirectResponse
    {
        $this->materials->delete($material);

        return back(fallback: route('admin.materials.index'))
            ->with('status', __('master-data.materials.deleted', ['name' => $material->name]));
    }

    public function deactivate(Material $material): RedirectResponse
    {
        $this->materials->deactivate($material);

        return back(fallback: route('admin.materials.index'))
            ->with('status', __('master-data.materials.deactivated', ['name' => $material->name]));
    }

    public function activate(Material $material): RedirectResponse
    {
        $this->materials->activate($material);

        return back(fallback: route('admin.materials.index'))
            ->with('status', __('master-data.materials.activated', ['name' => $material->name]));
    }
}
