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
        $rawSearch = $request->query('search', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $rawSort = $request->query('sort', 'newest');
        $sort = is_string($rawSort) ? $rawSort : 'newest';

        $rawType = $request->query('type', '');
        $type = is_string($rawType) ? $rawType : '';

        $rawCategoryId = $request->query('category_id');
        $categoryId = is_scalar($rawCategoryId) && $rawCategoryId !== '' ? (string) $rawCategoryId : null;

        $rawCondition = $request->query('condition', '');
        $condition = is_string($rawCondition) ? $rawCondition : '';

        $rawRegionId = $request->query('region_id');
        $regionId = is_scalar($rawRegionId) && $rawRegionId !== '' ? (string) $rawRegionId : null;

        $rawHubId = $request->query('hub_id');
        $hubId = is_scalar($rawHubId) && $rawHubId !== '' ? (string) $rawHubId : null;

        $rawMinPrice = $request->query('min_price');
        $minPrice = is_scalar($rawMinPrice) && is_numeric($rawMinPrice) && (float) $rawMinPrice >= 0
            ? (string) $rawMinPrice
            : null;

        $rawMaxPrice = $request->query('max_price');
        $maxPrice = is_scalar($rawMaxPrice) && is_numeric($rawMaxPrice) && (float) $rawMaxPrice >= 0
            ? (string) $rawMaxPrice
            : null;

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
        if ($categoryId !== null && ctype_digit($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        } else {
            $categoryId = null;
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
        if ($regionId !== null && ctype_digit($regionId)) {
            $regionIdInt = (int) $regionId;
            $query->where(function (Builder $q) use ($regionIdInt) {
                $q->where('region_id', $regionIdInt)
                    ->orWhere(function (Builder $fallback) use ($regionIdInt) {
                        $fallback->whereNull('region_id')
                            ->whereHas('seller', fn (Builder $seller) => $seller->where('region_id', $regionIdInt));
                    });
            });
        } else {
            $regionId = null;
        }

        // Filter hub komunitas (FR-012).
        if ($hubId !== null && ctype_digit($hubId)) {
            $query->where('hub_id', (int) $hubId);
        } else {
            $hubId = null;
        }

        // Filter harga minimum.
        if ($minPrice !== null) {
            $query->where('price', '>=', (float) $minPrice);
        }

        // Filter harga maksimum.
        if ($maxPrice !== null) {
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
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
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
