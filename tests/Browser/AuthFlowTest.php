<?php

namespace Tests\Browser;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Password;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthFlowTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_registrasi_dashboard_logout_lalu_login_kembali(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                ->type('@name', 'Budi Santoso')
                ->type('@email', 'budi@contoh.com')
                ->type('@password', 'rahasia123')
                ->type('@password-confirmation', 'rahasia123')
                ->click('@register-button')
                ->waitForLocation('/dashboard')
                ->assertSeeIn('@nav-user-name', 'Budi Santoso');

            $this->assertSame(UserRole::Warga, User::where('email', 'budi@contoh.com')->sole()->role);

            $browser->click('@logout-button')
                ->waitForLocation('/login')
                ->assertSeeIn('@alert', 'Anda telah keluar.')
                ->type('@email', 'budi@contoh.com')
                ->type('@password', 'rahasia123')
                ->click('@login-button')
                ->waitForLocation('/dashboard')
                ->assertSeeIn('@dashboard-user-name', 'Budi Santoso');
        });
    }

    public function test_admin_login_diarahkan_ke_halaman_admin(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin Sirkula',
            'email' => 'admin@sirkula.test',
            'password' => 'rahasia123',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('@email', 'admin@sirkula.test')
                ->type('@password', 'rahasia123')
                ->click('@login-button')
                ->waitForLocation('/admin')
                ->assertSeeIn('@admin-user-name', 'Admin Sirkula')
                ->assertSee('Anda masuk sebagai admin')
                ->assertPresent('nav a[href="'.route('admin.dashboard').'"]');
        });
    }

    public function test_lupa_kata_sandi_reset_lalu_login_dengan_kata_sandi_baru(): void
    {
        $user = User::factory()->create(['email' => 'siti@contoh.com']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->click('@forgot-password-link')
                ->waitForLocation('/forgot-password')
                ->type('@email', 'siti@contoh.com')
                ->click('@send-reset-link-button')
                ->waitFor('@alert')
                ->assertSeeIn('@alert', 'Jika email terdaftar, tautan reset telah dikirim.');

            // Token dibuat langsung agar tes tidak bergantung pada isi email.
            $token = Password::createToken($user);

            $browser->visit('/reset-password/'.$token.'?email='.urlencode($user->email))
                ->assertInputValue('@email', 'siti@contoh.com')
                ->type('@password', 'SandiBaru123')
                ->type('@password-confirmation', 'SandiBaru123')
                ->click('@reset-password-button')
                ->waitForLocation('/login')
                ->assertSeeIn('@alert', 'Kata sandi Anda berhasil diatur ulang.')
                ->type('@email', 'siti@contoh.com')
                ->type('@password', 'SandiBaru123')
                ->click('@login-button')
                ->waitForLocation('/dashboard');
        });
    }
}
