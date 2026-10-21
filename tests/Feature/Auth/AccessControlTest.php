<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private const PESAN_DITANGGUHKAN = 'Akun Anda sedang ditangguhkan. Hubungi admin.';

    /**
     * Tangguhkan akun langsung di database (seperti dilakukan admin dari perangkat lain),
     * lalu buang cache guard agar request berikutnya membaca ulang user dari database.
     */
    private function tangguhkan(User $user): void
    {
        User::whereKey($user->id)->update(['status' => UserStatus::Suspended->value]);

        $this->app['auth']->forgetGuards();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_warga_ke_halaman_admin_mendapat_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_dapat_membuka_halaman_admin_dan_melihat_tautannya(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_tautan_admin_tidak_tampil_untuk_warga(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_user_yang_ditangguhkan_saat_sedang_login_dikeluarkan(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->get('/dashboard')->assertOk();

        session()->put('penanda', 'sesi-lama');
        $this->tangguhkan($user);

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::PESAN_DITANGGUHKAN]);

        $this->assertGuest();
        $this->assertFalse(session()->has('penanda'), 'Sesi lama harus di-invalidate.');
    }

    public function test_admin_yang_ditangguhkan_diarahkan_ke_login_bukan_403(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->tangguhkan($admin);

        $this->get('/admin')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::PESAN_DITANGGUHKAN]);

        $this->assertGuest();
    }

    public function test_user_ditangguhkan_ikut_dikeluarkan_di_halaman_publik(): void
    {
        $this->actingAs(User::factory()->suspended()->create())
            ->get('/')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => self::PESAN_DITANGGUHKAN]);

        $this->assertGuest();
    }

    public function test_halaman_konfirmasi_kata_sandi_fortify_mengembalikan_404_untuk_user_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/user/confirm-password')
            ->assertNotFound();
    }

    public function test_halaman_konfirmasi_kata_sandi_fortify_mengarahkan_tamu_ke_login(): void
    {
        $this->get('/user/confirm-password')->assertRedirect(route('login'));
    }

    public function test_user_aktif_tetap_login_di_halaman_publik(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk();

        $this->assertAuthenticatedAs($user);
    }
}
