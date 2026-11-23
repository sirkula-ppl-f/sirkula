<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Hub;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'categories_active' => Category::active()->count(),
            'materials_active' => Material::active()->count(),
            'hubs_active' => Hub::active()->count(),
            'reference_prices_total' => ReferencePrice::count(),
            'guides_published' => Guide::published()->count(),
            'users_total' => User::count(),
            'users_suspended' => User::where('status', UserStatus::Suspended)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
