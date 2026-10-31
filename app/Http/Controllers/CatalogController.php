<?php

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Region;
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
        $regionId = $request->query('region_id');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');

        $query = Listing::purchasable()
            ->with(['category', 'seller.region']);

        // Pencarian sementara berdasarkan judul.
        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
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

        // Filter wilayah melalui wilayah penjual.
        if ($regionId !== null && $regionId !== '') {
            $query->whereHas('seller', function (Builder $seller) use ($regionId) {
                $seller->where('region_id', $regionId);
            });
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
            'regionId' => $regionId,
            'categories' => Category::active()->orderBy('name')->get(),
            'regions' => Region::orderBy('name')->get(),
            'listingTypes' => ListingType::cases(),
        ]);
    }

    public function show(int $id)
    {
        $listing = Listing::purchasable()
            ->with(['category', 'seller.region'])
            ->findOrFail($id);

        return view('catalog.show', [
            'listing' => $listing,
        ]);
    }
}
