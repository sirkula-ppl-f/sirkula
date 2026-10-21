<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PESAN_GAGAL = 'Email atau kata sandi salah.';

    private const PESAN_DITANGGUHKAN = 'Akun Anda sedang ditangguhkan. Hubungi admin.';

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('name="_token"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('dusk="email"', false)
            ->assertSee('dusk="password"', false)
            ->assertSee('dusk="login-button"', false)
            ->assertSee('dusk="forgot-password-link"', false)
            ->assertSee('dusk="register-link"', false);
    }

    public function test_warga_login_diarahkan_ke_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_diarahkan_ke_halaman_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_email_dengan_huruf_kapital_tetap_bisa_login(): void
    {
        $user = User::factory()->create(['email' => 'budi@contoh.com']);

        $this->post('/login', ['email' => ' BUDI@Contoh.com ', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_id_sesi_berubah_setelah_login_berhasil(): void
    {
        $user = User::factory()->create();
        $cookie = config('session.cookie');

        $this->get('/login')->assertOk();
        $idAwal = session()->getId();

        // Kontrol: cookie sesi membawa ID yang sama, dan login gagal tidak mengubahnya.
        // Tanpa cookie, setiap request test mendapat ID baru sehingga perbandingan tidak bermakna.
        $this->withCookie($cookie, $idAwal)
            ->post('/login', ['email' => $user->email, 'password' => 'salah12345'])
            ->assertSessionHasErrors('email');
        $this->assertSame($idAwal, session()->getId());

        $this->withCookie($cookie, $idAwal)
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($idAwal, session()->getId());
    }

    public function test_pesan_kata_sandi_salah_dan_email_tidak_terdaftar_identik(): void
    {
        User::factory()->create(['email' => 'budi@contoh.com']);

        $this->from('/login')
            ->post('/login', ['email' => 'budi@contoh.com', 'password' => 'salah12345'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::PESAN_GAGAL]);
        $errorsSandiSalah = session('errors')->getBag('default')->toArray();

        $this->from('/login')
            ->post('/login', ['email' => 'tidakada@contoh.com', 'password' => 'salah12345'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::PESAN_GAGAL]);
        $errorsTidakTerdaftar = session('errors')->getBag('default')->toArray();

        $this->assertSame($errorsSandiSalah, $errorsTidakTerdaftar);
        $this->assertGuest();
    }

    public function test_akun_ditangguhkan_dengan_kata_sandi_benar_ditolak(): void
    {
        $user = User::factory()->suspended()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => self::PESAN_DITANGGUHKAN]);

        $this->assertGuest();
    }

    public function test_akun_ditangguhkan_dengan_kata_sandi_salah_mendapat_pesan_generik(): void
    {
        $user = User::factory()->suspended()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'salah12345'])
            ->assertSessionHasErrors(['email' => self::PESAN_GAGAL]);

        $this->assertGuest();
    }

    public function test_rate_limit_aktif_setelah_lima_percobaan(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah12345'])
                ->assertSessionHasErrors(['email' => self::PESAN_GAGAL]);
        }

        // Percobaan ke-6 dengan kata sandi benar tetap ditolak.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('login'))
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertMatchesRegularExpression(
            '/^Terlalu banyak percobaan\. Silakan coba lagi dalam \d+ detik\.$/',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_rate_limit_dihitung_per_email_dan_ip(): void
    {
        $diblokir = User::factory()->create();
        $lain = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $diblokir->email, 'password' => 'salah12345']);
        }

        $this->post('/login', ['email' => $lain->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($lain);
    }

    public function test_intended_tetap_dihormati(): void
    {
        $admin = User::factory()->admin()->create();

        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_intended_ke_admin_dibuang_untuk_warga(): void
    {
        $warga = User::factory()->create();

        $this->get('/admin')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $warga->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($warga);
    }

    public function test_user_login_yang_membuka_halaman_login_diarahkan_sesuai_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect(route('dashboard'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_cookie_sesi_http_only_same_site_lax_dan_secure_dari_konfigurasi(): void
    {
        $cookie = $this->get('/login')->getCookie(config('session.cookie'), false);

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertFalse($cookie->isSecure());

        config(['session.secure' => true]);

        $this->assertTrue($this->get('/login')->getCookie(config('session.cookie'), false)->isSecure());
    }
}
