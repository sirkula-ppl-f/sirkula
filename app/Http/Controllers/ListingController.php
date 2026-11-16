<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ListingController extends Controller
{
    public function create()
    {
        // TODO: ganti ke hub dengan keanggotaan APPROVED setelah PBI-02 merge
       $hubs = Hub::all();
        $categories = Category::all();
        $regions = \App\Models\Region::all();

        return view('listings.create', compact('hubs', 'categories', 'regions'));
    }

    public function store(StoreListingRequest $request)
    {
        $hub = Hub::findOrFail($request->hub_id);

        $statusListing = $request->action === 'draft' ? 'DRAFT' : 'PENDING_REVIEW';

        $listing = Listing::create([
            'user_id' => Auth::id(),
            'listing_type' => $request->tipe,
            'title' => $request->judul,
            'category_id' => $request->kategori_id,
            'item_condition' => $request->kondisi,
            'price' => $request->harga,
            'stock' => $request->stok,
            'hub_id' => $request->hub_id,
            'region_id' => $hub->region_id,
            'description' => $request->deskripsi,
            'review_status' => $statusListing,
        ]);

        if ($request->hasFile('fotos')) {
            $manager = ImageManager::usingDriver(Driver::class);

            foreach ($request->file('fotos') as $index => $file) {
                $filename = Str::random(40).'.jpg';
                $path = 'listings/'.$filename;

                $image = $manager->decode($file);

                $encoded = $image->encodeUsingFormat(Format::JPEG, quality: 90);

                Storage::disk('public')->put($path, (string) $encoded);

                $listing->images()->create([
                    'path_foto' => $path,
                    'is_primary' => $index === 0 ? true : false,
                ]);
            }
        }

        $pesanSukses = $statusListing === 'DRAFT'
            ? 'Listing berhasil disimpan sebagai draf.'
            : 'Listing berhasil dibuat dan sedang menunggu verifikasi Hub.';

        return redirect()->route('listings.create')->with('success', $pesanSukses);
    }
}
