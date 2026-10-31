<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingType;
use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ListsMasterData;

    public function __construct(private CategoryService $categories) {}

    public function index(Request $request): View|RedirectResponse
    {
        $filters = [
            'search' => $this->queryText($request, 'cari'),
            'status' => $this->statusFilter($request),
            'type' => ListingType::tryFrom((string) $this->queryText($request, 'tipe')),
        ];

        $categories = $this->categories->paginate([
            'search' => $filters['search'],
            'active' => $filters['status'] === null ? null : $filters['status'] === 'aktif',
            'type' => $filters['type'],
        ]);

        return $this->redirectIfPageEmpty($request, $categories) ?? view('admin.categories.index', [
            'categories' => $categories,
            'filters' => $filters,
            'listingTypes' => ListingType::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'listingTypes' => ListingType::cases(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('status', __('master-data.categories.created', ['name' => $category->name]));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'listingTypes' => ListingType::cases(),
            'typesInUse' => $category->listingTypesInUse(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()->route('admin.categories.index')
            ->with('status', __('master-data.categories.updated', ['name' => $category->name]));
    }

    /**
     * Kategori yang sudah dipakai ditolak lewat MasterDataInUseException (flash error, bukan 500).
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        return back(fallback: route('admin.categories.index'))
            ->with('status', __('master-data.categories.deleted', ['name' => $category->name]));
    }

    public function deactivate(Category $category): RedirectResponse
    {
        $this->categories->deactivate($category);

        return back(fallback: route('admin.categories.index'))
            ->with('status', __('master-data.categories.deactivated', ['name' => $category->name]));
    }

    public function activate(Category $category): RedirectResponse
    {
        $this->categories->activate($category);

        return back(fallback: route('admin.categories.index'))
            ->with('status', __('master-data.categories.activated', ['name' => $category->name]));
    }
}
