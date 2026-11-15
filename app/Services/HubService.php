<?php

namespace App\Services;

use App\Models\Hub;
use App\Models\HubManager;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HubService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Hub::query()->with('region');

        $search = $filters['search'] ?? '';

        if (is_string($search) && $search !== '') {
            $escaped = str_replace(
                ['!', '%', '_'],
                ['!!', '!%', '!_'],
                $search
            );

            $query->whereRaw(
                "name LIKE ? ESCAPE '!'",
                ["%{$escaped}%"]
            );
        }

        $status = $filters['status'] ?? '';

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        return $query
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Hub
    {
        return Hub::create([
            ...$data,
            'status' => 'ACTIVE',
        ]);
    }

    public function update(Hub $hub, array $data): Hub
    {
        $hub->update($data);

        return $hub->refresh();
    }

    public function archive(Hub $hub): void
    {
        DB::transaction(function () use ($hub) {
            $lockedHub = Hub::query()
                ->whereKey($hub->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedHub->status === 'ARCHIVED') {
                return;
            }

            $lockedHub->update([
                'status' => 'ARCHIVED',
            ]);

            $lockedHub->managers()
                ->where('assignment_status', 'ACTIVE')
                ->update([
                    'assignment_status' => 'INACTIVE',
                    'deactivated_at' => now(),
                ]);
        });
    }

    public function assignManager(Hub $hub, int $userId): void
    {
        DB::transaction(function () use ($hub, $userId) {
            $lockedHub = Hub::query()
                ->whereKey($hub->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedHub->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'hub' => 'Hub yang diarsipkan tidak dapat menerima pengelola.',
                ]);
            }

            $user = User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Pengguna harus berstatus aktif.',
                ]);
            }

            $assignment = HubManager::query()
                ->where('hub_id', $lockedHub->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($assignment?->assignment_status === 'ACTIVE') {
                throw ValidationException::withMessages([
                    'user_id' => 'Pengguna sudah menjadi pengelola Hub ini.',
                ]);
            }

            if ($assignment) {
                $assignment->update([
                    'assignment_status' => 'ACTIVE',
                    'assigned_at' => now(),
                    'deactivated_at' => null,
                ]);

                return;
            }

            HubManager::create([
                'hub_id' => $lockedHub->id,
                'user_id' => $user->id,
                'assignment_status' => 'ACTIVE',
                'assigned_at' => now(),
            ]);
        });
    }

    public function deactivateManager(
        Hub $hub,
        HubManager $manager
    ): bool {
        return DB::transaction(function () use ($hub, $manager) {
            $lockedHub = Hub::query()
                ->whereKey($hub->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedManager = HubManager::query()
                ->whereKey($manager->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedManager->hub_id !== $lockedHub->id) {
                abort(404);
            }

            if ($lockedManager->assignment_status !== 'ACTIVE') {
                return false;
            }

            $lockedManager->update([
                'assignment_status' => 'INACTIVE',
                'deactivated_at' => now(),
            ]);

            return true;
        });
    }
}
