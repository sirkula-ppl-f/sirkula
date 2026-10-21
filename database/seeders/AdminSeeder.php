<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Buat atau perbarui akun admin dari ADMIN_NAME, ADMIN_EMAIL, dan ADMIN_PASSWORD di .env.
     */
    public function run(): void
    {
        $admin = config('sirkula.admin');

        if (blank($admin['name']) || blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('AdminSeeder dilewati: ADMIN_NAME, ADMIN_EMAIL, atau ADMIN_PASSWORD di .env masih kosong.');

            return;
        }

        $user = User::firstOrNew(['email' => $admin['email']]);

        $user->forceFill([
            'name' => $admin['name'],
            'password' => $admin['password'],
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ])->save();

        $this->command?->info("Akun admin {$user->email} siap dipakai.");
    }
}
