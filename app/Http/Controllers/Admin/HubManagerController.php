<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\HubManager;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HubManagerController extends Controller
{
    /**
     * Menugaskan pengguna sebagai pengelola Hub.
     */
    public function store(Request $request, Hub $hub): RedirectResponse
    {
        if ($hub->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'hub' => 'Hub yang diarsipkan tidak dapat menerima pengelola.',
            ]);
        }

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'user_id' => 'Pengguna harus berstatus aktif.',
            ]);
        }

        DB::transaction(function () use ($hub, $user) {
            $assignment = HubManager::query()
                ->where('hub_id', $hub->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($assignment) {
                if ($assignment->assignment_status === 'ACTIVE') {
                    throw ValidationException::withMessages([
                        'user_id' => 'Pengguna sudah menjadi pengelola Hub ini.',
                    ]);
                }

                $assignment->update([
                    'assignment_status' => 'ACTIVE',
                    'assigned_at' => now(),
                    'deactivated_at' => null,
                ]);

                return;
            }

            HubManager::create([
                'hub_id' => $hub->id,
                'user_id' => $user->id,
                'assignment_status' => 'ACTIVE',
                'assigned_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Pengelola Hub berhasil ditugaskan.');
    }

    /**
     * Menonaktifkan penugasan pengelola Hub.
     */
    public function deactivate(
        Hub $hub,
        HubManager $manager
    ): RedirectResponse {
        if ($manager->hub_id !== $hub->id) {
            abort(404);
        }

        if ($manager->assignment_status !== 'ACTIVE') {
            return redirect()
                ->route('admin.hubs.show', $hub)
                ->with('info', 'Penugasan sudah tidak aktif.');
        }

        $manager->update([
            'assignment_status' => 'INACTIVE',
            'deactivated_at' => now(),
        ]);

        return redirect()
            ->route('admin.hubs.show', $hub)
            ->with('success', 'Penugasan pengelola berhasil dinonaktifkan.');
    }
}
