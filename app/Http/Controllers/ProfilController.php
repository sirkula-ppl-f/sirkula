<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $regions = Region::all(); // Mengambil semua data wilayah untuk pilihan dropdown dari Sabang sampai Merauke

        return view('profil.edit', compact('user', 'regions'));
    }

    /**
     * Memperbarui informasi profil publik pengguna.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi disesuaikan dengan 2 input field yang ada di mockup (name dan region_id)
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'region_id' => ['required', 'exists:regions,id'], // Memastikan ID wilayah valid dari tabel regions
        ]);

        // Menyimpan perubahan data ke database
        $user->name = $request->name;
        $user->region_id = $request->region_id;
        $user->save();

        return redirect()->route('profil.edit')->with('success', 'Profil publik berhasil diperbarui.');
    }
}
