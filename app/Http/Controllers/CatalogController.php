<?php

namespace App\Http\Controllers;

use App\Enums\ItemCondition;
use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use App\Services\ReferencePriceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'newest');
        $type = (string) $request->query('type', '');
        $categoryId = $request->query('category_id');
        $condition = (string) $request->query('condition', '');
        $regionId = $request->query('region_id');
        $hubId = $request->query('hub_id');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');

        $query = Listing::purchasable()
            ->with(['category', 'material', 'hub', 'region', 'seller.region', 'images']);

        // Pencarian berdasarkan judul atau deskripsi dengan escape karakter LIKE (FR-012).
        if ($search !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
            $pattern = "%{$escaped}%";
            $query->where(function (Builder $q) use ($pattern) {
                $q->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        // Filter tipe listing.
        $validTypes = array_map(
            fn (ListingType $listingType) => $listingType->value,
            ListingType::cases()
        );

        if (in_array($type, $validTypes, true)) {
            $query->where('listing_type', $type);
        } else {
            $type = '';
        }

        // Filter kategori menggunakan category_id.
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        // Filter kondisi barang (FR-012).
        $validConditions = array_map(
            fn (ItemCondition $itemCondition) => $itemCondition->value,
            ItemCondition::cases()
        );

        if (in_array($condition, $validConditions, true)) {
            $query->where('item_condition', $condition);
        } else {
            $condition = '';
        }

        // Filter wilayah melalui wilayah listing atau wilayah penjual.
        if ($regionId !== null && $regionId !== '') {
            $query->where(function (Builder $q) use ($regionId) {
                $q->where('region_id', $regionId)
                    ->orWhere(function (Builder $fallback) use ($regionId) {
                        $fallback->whereNull('region_id')
                            ->whereHas('seller', fn (Builder $seller) => $seller->where('region_id', $regionId));
                    });
            });
        }

        // Filter hub komunitas (FR-012).
        if ($hubId !== null && $hubId !== '') {
            $query->where('hub_id', $hubId);
        }

        // Filter harga minimum.
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $query->where('price', '>=', (float) $minPrice);
        }

        // Filter harga maksimum.
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // Pengurutan listing.
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;

            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;

            default:
                $query->latest();
                $sort = 'newest';
                break;
        }

        $listings = $query->paginate(12)->withQueryString();

        return view('catalog.index', [
            'listings' => $listings,
            'search' => $search,
            'sort' => $sort,
            'type' => $type,
            'categoryId' => $categoryId,
            'condition' => $condition,
            'regionId' => $regionId,
            'hubId' => $hubId,
            'categories' => Category::active()->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'regions' => Region::orderBy('name')->get(),
            'hubs' => Hub::active()->orderBy('name')->get(),
            'listingTypes' => ListingType::cases(),
        ]);
    }

    public function show(int $id, ReferencePriceService $referencePriceService)
    {
        $listing = Listing::purchasable()
            ->with(['category', 'material', 'hub', 'region', 'seller.region', 'images'])
            ->findOrFail($id);

        $effectiveRegion = $listing->region ?? $listing->seller?->region;
        $referencePrice = ($listing->material && $effectiveRegion)
            ? $referencePriceService->latestFor($listing->material, $effectiveRegion)
            : null;

        return view('catalog.show', [
            'listing' => $listing,
            'effectiveRegion' => $effectiveRegion,
            'referencePrice' => $referencePrice,
        ]);
    }
}
