<?php

namespace App\Http\Controllers;

use App\Models\Guide;

class GuideTrackingController extends Controller
{
    public function view(Guide $guide)
    {
        abort_unless($guide->status === 'published', 404);
        $guide->increment('views_count');

        return response()->noContent();
    }

    public function interact(Guide $guide)
    {
        abort_unless($guide->status === 'published', 404);
        $guide->increment('interactions_count');

        return response()->noContent();
    }
}
