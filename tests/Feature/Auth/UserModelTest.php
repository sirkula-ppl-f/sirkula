<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_dan_status_di_cast_ke_enum_dengan_default_warga_aktif(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Warga, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);

        $fresh = $user->fresh();

        $this->assertSame(UserRole::Warga, $fresh->role);
        $this->assertSame(UserStatus::Active, $fresh->status);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'WARGA', 'status' => 'ACTIVE']);
    }

    public function test_email_disimpan_huruf_kecil_dan_tanpa_spasi(): void
    {
        $user = User::factory()->create(['email' => '  Budi.Santoso@Contoh.COM ']);

        $this->assertSame('budi.santoso@contoh.com', $user->email);
        $this->assertDatabaseHas('users', ['email' => 'budi.santoso@contoh.com']);
    }

    public function test_role_dan_status_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Penyusup',
            'email' => 'penyusup@contoh.com',
            'password' => 'rahasia123',
            'role' => 'ADMIN',
            'status' => 'SUSPENDED',
        ]);

        $fresh = $user->fresh();

        $this->assertSame(UserRole::Warga, $fresh->role);
        $this->assertSame(UserStatus::Active, $fresh->status);
    }

    public function test_helper_is_admin_dan_is_active(): void
    {
        $warga = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ditangguhkan = User::factory()->suspended()->create();

        $this->assertFalse($warga->isAdmin());
        $this->assertTrue($warga->isActive());

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isActive());

        $this->assertFalse($ditangguhkan->isAdmin());
        $this->assertFalse($ditangguhkan->isActive());
    }
}
