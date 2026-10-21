<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_akun_admin_dari_konfigurasi_env(): void
    {
        config(['sirkula.admin' => [
            'name' => 'Admin Sirkula',
            'email' => 'Admin@Sirkula.test',
            'password' => 'rahasia123',
        ]]);

        $this->seed(AdminSeeder::class);

        $admin = User::sole();

        $this->assertSame('Admin Sirkula', $admin->name);
        $this->assertSame('admin@sirkula.test', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
    }

    public function test_dilewati_jika_konfigurasi_kosong(): void
    {
        config(['sirkula.admin' => [
            'name' => 'Admin Sirkula',
            'email' => 'admin@sirkula.test',
            'password' => null,
        ]]);

        $this->artisan('db:seed', ['--class' => AdminSeeder::class])
            ->expectsOutputToContain('AdminSeeder dilewati')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_aman_dijalankan_berulang_kali(): void
    {
        config(['sirkula.admin' => [
            'name' => 'Admin Sirkula',
            'email' => 'admin@sirkula.test',
            'password' => 'rahasia123',
        ]]);

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
    }
}
