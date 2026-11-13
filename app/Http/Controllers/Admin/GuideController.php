<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $placements = $data['placements'];
        unset($data['placements']);

        $data['slug'] = str($data['title'])->slug().'-'.substr(uniqid(), -5);

        $guide = Guide::create($data);
        $guide->syncPlacements($placements);

        return redirect()->route('admin.guides.index')->with('success', 'Panduan dibuat.');
    }

    public function edit(Guide $guide)
    {
        $guide->load('placements');

        return view('admin.guides.form', compact('guide'));
    }

    public function update(Request $request, Guide $guide)
    {
        $data = $this->validated($request);
        $placements = $data['placements'];
        unset($data['placements']);

        $guide->update($data);
        $guide->syncPlacements($placements);

        return redirect()->route('admin.guides.index')->with('success', 'Panduan diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_keys(Guide::STATUSES))],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*' => [Rule::in(array_keys(Guide::PLACEMENTS))],
        ]);
    }
}
