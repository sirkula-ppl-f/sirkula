<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\ListingImage;
// Asumsi kamu punya model Material dan Storefront
use App\Models\Material;
use App\Models\Region;
use App\Models\Storefront;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ListingController extends Controller
{
    public function index()
    {
        // Menampilkan daftar listing milik user yang login
        $listings = Listing::where('user_id', auth()->id())->get();

        return view('listings.index', compact('listings'));
    }

    public function create()
    {
        $categories = Category::all();
        $hubs = Hub::all();
        $regions = Region::all();
        $materials = Material::all();

        // Asumsi relasi user memiliki storefronts
        $storefronts = Storefront::where('user_id', auth()->id())->get();

        return view('listings.create', compact('categories', 'hubs', 'regions', 'materials', 'storefronts'));
    }

    public function store(StoreListingRequest $request)
    {
        $validated = $request->validated();
        $status = $validated['action'] === 'draft' ? 'DRAFT' : 'PENDING_REVIEW';

        $listing = Listing::create([
            'user_id' => auth()->id(),
            'listing_type' => $validated['listing_type'],
            'title' => $validated['title'],
            'category_id' => $validated['category_id'] ?? null,
            'material_id' => $validated['material_id'] ?? null,
            'storefront_id' => $validated['storefront_id'] ?? null,
            'condition' => $validated['condition'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'weight_per_lot' => $validated['weight_per_lot'] ?? null,
            'hub_id' => $validated['hub_id'],
            'region_id' => $validated['region_id'],
            'description' => $validated['description'],
            'status' => $status,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $filename = Str::random(40).'.jpg';
                $image = Image::read($file)->encodeByMediaType('image/jpeg');
                Storage::disk('public')->put('listings/'.$filename, (string) $image);

                ListingImage::create([
                    'listing_id' => $listing->id,
                    'filename' => 'listings/'.$filename,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        $message = $status === 'DRAFT' ? 'Listing berhasil disimpan sebagai draf.' : 'Listing berhasil diajukan ke Hub.';

        return redirect()->route('listings.index')->with('success', $message);
    }
}
