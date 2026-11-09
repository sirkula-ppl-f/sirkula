<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\Region;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// Namespace khusus Intervention Image V4
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ListingController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $hubs = Hub::all();
        $regions = Region::all();

        return view('listings.create', compact('categories', 'hubs', 'regions'));
    }

    public function store(StoreListingRequest $request)
    {
        $statusListing = $request->action === 'draft' ? 'DRAFT' : 'PENDING_REVIEW';

        $listing = Listing::create([
            'user_id' => auth()->id(),
            'listing_type' => $request->tipe,
            'title' => $request->judul,
            'category_id' => $request->kategori_id,
            'condition' => $request->kondisi,
            'price' => $request->harga,
            'stock' => $request->stok,
            'hub_id' => $request->hub_id,
            'region_id' => $request->wilayah_id,
            'description' => $request->deskripsi,
            'status' => $statusListing,
        ]);

        if ($request->hasFile('fotos')) {

            // Instansiasi ImageManager menggunakan Driver versi 4
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
