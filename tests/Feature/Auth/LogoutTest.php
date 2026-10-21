<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Laravel melewati pengecekan CSRF saat unit test. Ganti middleware-nya
     * dengan versi yang tetap memeriksa token agar penolakan bisa diuji.
     */
    private function aktifkanCsrf(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    private function tokenCsrf(): string
    {
        session()->regenerateToken();

        return session()->token();
    }

    public function test_navbar_memuat_form_logout_post_dengan_csrf(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('<form method="POST" action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('dusk="logout-button"', false)
            ->assertDontSee('href="'.route('logout').'"', false);
    }

    public function test_logout_membatalkan_sesi_dan_mengarahkan_ke_login_dengan_pesan(): void
    {
        $user = User::factory()->create();
        $cookie = config('session.cookie');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $idLogin = session()->getId();

        // Kontrol: cookie membawa sesi yang sama selama user masih login.
        $this->withCookie($cookie, $idLogin)->get('/dashboard')->assertOk();
        $this->assertSame($idLogin, session()->getId());

        session()->put('penanda', 'sesi-lama');
        $tokenLama = session()->token();

        $this->withCookie($cookie, $idLogin)
            ->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Anda telah keluar.');

        $this->assertGuest();
        $this->assertNotSame($idLogin, session()->getId());
        $this->assertNotSame($tokenLama, session()->token());
        $this->assertFalse(session()->has('penanda'), 'Data sesi lama harus dibuang.');

        $this->get('/login')->assertOk()->assertSee('Anda telah keluar.');
    }

    public function test_halaman_terproteksi_setelah_logout_diarahkan_ke_login(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->get('/dashboard')->assertOk();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_get_logout_tidak_melakukan_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/logout')
            ->assertStatus(405);

        $this->assertAuthenticatedAs($user);
    }

    public function test_post_logout_tanpa_csrf_ditolak(): void
    {
        $this->aktifkanCsrf();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertStatus(419)
            ->assertSee('Halaman Kedaluwarsa');

        $this->assertAuthenticatedAs($user);
    }

    public function test_post_logout_dengan_csrf_valid_berhasil(): void
    {
        $this->aktifkanCsrf();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout', ['_token' => $this->tokenCsrf()])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_ditangguhkan_yang_logout_diarahkan_dengan_pesan_ditangguhkan(): void
    {
        $this->aktifkanCsrf();
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)
            ->post('/logout', ['_token' => $this->tokenCsrf()])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Akun Anda sedang ditangguhkan. Hubungi admin.'])
            ->assertSessionMissing('status');

        $this->assertGuest();
    }
}
