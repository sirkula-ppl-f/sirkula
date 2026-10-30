<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna dengan filter dan pencarian sederhana.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // 1. Fitur pencarian nama atau email (FR-004)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Filter berdasarkan status akun (ACTIVE / SUSPENDED)
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Ambil data dengan pagination (10 user per halaman)
        $users = $query->latest()->paginate(10)->withQueryString();

        $activeAdminCount = User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->count();

        return view('admin.users.index', compact('users', 'activeAdminCount'));
    }

    /**
     * Menangguhkan (suspend) akun pengguna (FR-004).
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        // 1. Validasi input: alasan penangguhan wajib diisi
        session()->flash('selected_user_id', $user->id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan penangguhan akun wajib diisi.',
            'reason.min' => 'Alasan penangguhan minimal 5 karakter.',
            'reason.max' => 'Alasan penangguhan maksimal 500 karakter.',
        ]);

        // 2. Guard: Admin tidak boleh men-suspend dirinya sendiri
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menangguhkan akun Anda sendiri.');
        }

        // 3. Guard: Tidak boleh men-suspend Admin aktif terakhir di sistem (FR-004)
        if ($user->isAdmin()) {
            $activeAdminCount = User::query()
                ->where('role', UserRole::Admin)
                ->where('status', UserStatus::Active)
                ->count();

            if ($activeAdminCount <= 1) {
                return back()->with('error', 'Tidak dapat menangguhkan admin aktif terakhir di sistem.');
            }
        }

        // 4. Cek jika akun sudah dalam keadaan ditangguhkan
        if ($user->status === UserStatus::Suspended) {
            return back()->with('error', 'Akun pengguna ini sudah dalam status ditangguhkan.');
        }

        // 5. Eksekusi penangguhan, pencabutan sesi, dan pencatatan audit log
        DB::transaction(function () use ($request, $user, $validated) {
            // Ubah status akun menjadi SUSPENDED
            $user->forceFill(['status' => UserStatus::Suspended])->save();

            // Cabut seluruh sesi aktif user seketika di semua perangkat (FR-004)
            $user->revokeAllSessions();

            // Catat ke audit log (FR-021)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'USER_SUSPENDED',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'result' => 'SUCCESS',
                'reason' => $validated['reason'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Akun pengguna {$user->name} berhasil ditangguhkan.");
    }

    /**
     * Mengaktifkan kembali akun pengguna yang ditangguhkan (FR-004).
     */
    public function activate(Request $request, User $user): RedirectResponse
    {
        session()->flash('selected_user_id', $user->id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan pengaktifan akun wajib diisi.',
            'reason.min' => 'Alasan pengaktifan minimal 5 karakter.',
            'reason.max' => 'Alasan pengaktifan maksimal 500 karakter.',
        ]);

        // Cek jika akun sudah berstatus aktif
        if ($user->status === UserStatus::Active) {
            return back()->with('error', 'Akun pengguna ini sudah berstatus aktif.');
        }

        DB::transaction(function () use ($request, $user, $validated) {
            // Ubah status akun kembali menjadi ACTIVE
            $user->forceFill(['status' => UserStatus::Active])->save();

            // Catat pengaktifan ke audit log (FR-021)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'USER_ACTIVATED',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'result' => 'SUCCESS',
                'reason' => $validated['reason'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Akun pengguna {$user->name} berhasil diaktifkan kembali.");
    }
}
