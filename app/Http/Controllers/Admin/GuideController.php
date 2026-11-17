<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuideRequest;
use App\Models\Guide;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GuideController extends Controller
{
    public function index()
    {
        $guides = Guide::with('placements')->orderBy('id')->get();
        $preview = $guides->firstWhere('status', 'published');

        return view('admin.guides.index', compact('guides', 'preview'));
    }

    public function create()
    {
        return view('admin.guides.form', ['guide' => new Guide]);
    }

    public function store(GuideRequest $request)
    {
        $data = $request->validated();
        $placements = Arr::pull($data, 'placements');
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));

        $guide = Guide::create($data);
        $guide->syncPlacements($placements);

        return redirect()->route('admin.guides.index')->with('status', __('guides.flash.created'));
    }

    public function edit(Guide $guide)
    {
        $guide->load('placements');

        return view('admin.guides.form', compact('guide'));
    }

    public function update(GuideRequest $request, Guide $guide)
    {
        $data = $request->validated();
        $placements = Arr::pull($data, 'placements');

        $guide->update($data);
        $guide->syncPlacements($placements);

        return redirect()->route('admin.guides.index')->with('status', __('guides.flash.updated'));
    }
}
