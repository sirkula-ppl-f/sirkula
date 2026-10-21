<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const PESAN_TERKIRIM = 'Jika email terdaftar, tautan reset telah dikirim.';

    private const PESAN_TOKEN = 'Tautan reset kata sandi tidak valid atau sudah kedaluwarsa.';

    private const PESAN_BERHASIL = 'Kata sandi Anda berhasil diatur ulang. Silakan masuk dengan kata sandi baru.';

    /**
     * @param  array<string, string>  $override
     * @return array<string, string>
     */
    private function dataReset(User $user, string $token, array $override = []): array
    {
        return array_merge([
            'token' => $token,
            'email' => $user->email,
            'password' => 'SandiBaru123',
            'password_confirmation' => 'SandiBaru123',
        ], $override);
    }

    private function urlReset(User $user, string $token): string
    {
        return route('password.reset', ['token' => $token, 'email' => $user->email]);
    }

    private function buatSesi(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->getTimestamp(),
        ]);
    }

    public function test_halaman_lupa_kata_sandi_dapat_dibuka(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Lupa Kata Sandi')
            ->assertSee('60 menit')
            ->assertSee('name="_token"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('dusk="email"', false)
            ->assertSee('dusk="send-reset-link-button"', false)
            ->assertSee('dusk="back-to-login-link"', false);
    }

    public function test_halaman_reset_memuat_token_dan_email_terisi(): void
    {
        $this->get('/reset-password/token-contoh?email=budi@contoh.com')
            ->assertOk()
            ->assertSee('Atur Ulang Kata Sandi')
            ->assertSee('name="token" value="token-contoh"', false)
            ->assertSee('value="budi@contoh.com"', false)
            ->assertSee('readonly', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('dusk="password"', false)
            ->assertSee('dusk="password-confirmation"', false)
            ->assertSee('dusk="reset-password-button"', false);
    }

    public function test_respons_email_terdaftar_dan_tidak_terdaftar_identik(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $terdaftar = $this->post('/forgot-password', ['email' => $user->email]);
        $terdaftar->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::PESAN_TERKIRIM)
            ->assertSessionHasNoErrors();

        $tidakTerdaftar = $this->post('/forgot-password', ['email' => 'tidakada@contoh.com']);
        $tidakTerdaftar->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::PESAN_TERKIRIM)
            ->assertSessionHasNoErrors();

        $this->assertSame($terdaftar->getStatusCode(), $tidakTerdaftar->getStatusCode());
        $this->assertSame($terdaftar->headers->get('Location'), $tidakTerdaftar->headers->get('Location'));

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_permintaan_berulang_dalam_60_detik_tetap_generik(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', self::PESAN_TERKIRIM);

        // Throttle broker bawaan menolak token baru < 60 detik, tetapi responsnya tetap sama.
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::PESAN_TERKIRIM)
            ->assertSessionHasNoErrors();

        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_email_reset_berbahasa_indonesia_dan_menyebut_60_menit(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);
            $html = (string) $mail->render();

            $this->assertSame('Atur ulang kata sandi Anda', $mail->subject);
            $this->assertSame('Atur Ulang Kata Sandi', $mail->actionText);
            $this->assertSame($this->urlReset($user, $notification->token), $mail->actionUrl);
            $this->assertStringContainsString('berlaku selama 60 menit', $html);

            return true;
        });
    }

    public function test_reset_berhasil_tanpa_login_otomatis(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->dataReset($user, $token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', self::PESAN_BERHASIL);

        $this->assertGuest();
        $this->assertTrue(Hash::check('SandiBaru123', $user->fresh()->password));

        $this->post('/login', ['email' => $user->email, 'password' => 'SandiBaru123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_token_masih_berlaku_pada_menit_ke_59(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->travel(59)->minutes();

        $this->post('/reset-password', $this->dataReset($user, $token))
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();
    }

    public function test_token_kedaluwarsa_setelah_61_menit(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        $this->from($this->urlReset($user, $token))
            ->post('/reset-password', $this->dataReset($user, $token))
            ->assertRedirect($this->urlReset($user, $token))
            ->assertSessionHasErrors(['email' => self::PESAN_TOKEN]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_token_hanya_bisa_dipakai_sekali(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', $this->dataReset($user, $token))
            ->assertRedirect(route('login'));

        $this->from($this->urlReset($user, $token))
            ->post('/reset-password', $this->dataReset($user, $token, [
                'password' => 'SandiLain456',
                'password_confirmation' => 'SandiLain456',
            ]))
            ->assertSessionHasErrors(['email' => self::PESAN_TOKEN]);

        $this->assertTrue(Hash::check('SandiBaru123', $user->fresh()->password));
    }

    public function test_email_tidak_terdaftar_di_form_reset_mendapat_pesan_yang_sama(): void
    {
        $user = User::factory()->create();

        $this->from('/reset-password/token-salah')
            ->post('/reset-password', $this->dataReset($user, 'token-salah'))
            ->assertSessionHasErrors(['email' => self::PESAN_TOKEN]);

        $this->from('/reset-password/token-salah')
            ->post('/reset-password', $this->dataReset($user, 'token-salah', ['email' => 'tidakada@contoh.com']))
            ->assertSessionHasErrors(['email' => self::PESAN_TOKEN]);
    }

    public function test_semua_sesi_user_dicabut_setelah_reset(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $this->buatSesi($user);
        $this->buatSesi($user);
        $this->buatSesi($lain);
        $rememberTokenLama = $user->remember_token;

        $this->post('/reset-password', $this->dataReset($user, Password::createToken($user)))
            ->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $lain->id)->count());
        $this->assertNotSame($rememberTokenLama, $user->fresh()->remember_token);
    }

    public function test_rate_limit_permintaan_tautan_tetap_generik(): void
    {
        // Matikan throttle broker (60 detik) agar yang diuji murni rate limit 5/menit.
        config(['auth.passwords.users.throttle' => 0]);
        Notification::fake();
        $user = User::factory()->create();
        $lain = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertSessionHas('status', self::PESAN_TERKIRIM);
        }
        Notification::assertSentTimes(ResetPassword::class, 5);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::PESAN_TERKIRIM)
            ->assertSessionHasNoErrors();
        Notification::assertSentTimes(ResetPassword::class, 5);

        // Key memakai email + IP, jadi email lain dari IP yang sama tetap diproses.
        $this->post('/forgot-password', ['email' => $lain->email]);
        Notification::assertSentTo($lain, ResetPassword::class);
    }

    public function test_rate_limit_per_ip_membatasi_banyak_email_dari_satu_ip(): void
    {
        config(['auth.passwords.users.throttle' => 0]);
        Notification::fake();
        $users = User::factory()->count(21)->create();

        $normal = $this->post('/forgot-password', ['email' => $users[0]->email]);
        foreach ($users->slice(1, 19) as $user) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertSessionHas('status', self::PESAN_TERKIRIM);
        }
        Notification::assertSentTimes(ResetPassword::class, 20);

        // Permintaan ke-21 dengan email baru dari IP yang sama: respons tetap identik, tanpa email.
        $keduaPuluhSatu = $this->post('/forgot-password', ['email' => $users[20]->email]);
        $keduaPuluhSatu->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::PESAN_TERKIRIM)
            ->assertSessionHasNoErrors();

        $this->assertSame($normal->getStatusCode(), $keduaPuluhSatu->getStatusCode());
        $this->assertSame($normal->headers->get('Location'), $keduaPuluhSatu->headers->get('Location'));
        Notification::assertSentTimes(ResetPassword::class, 20);
        Notification::assertNotSentTo($users[20], ResetPassword::class);

        // Permintaan dari IP lain tetap diproses.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post('/forgot-password', ['email' => $users[20]->email])
            ->assertSessionHas('status', self::PESAN_TERKIRIM);
        Notification::assertSentTo($users[20], ResetPassword::class);
    }

    public function test_rate_limit_submit_reset_aktif_setelah_lima_percobaan(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $url = $this->urlReset($user, $token);

        for ($i = 0; $i < 5; $i++) {
            $this->from($url)
                ->post('/reset-password', $this->dataReset($user, 'token-salah'))
                ->assertSessionHasErrors(['email' => self::PESAN_TOKEN]);
        }

        // Percobaan ke-6 dengan token benar tetap ditolak.
        $this->from($url)
            ->post('/reset-password', $this->dataReset($user, $token))
            ->assertRedirect($url)
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.token');

        $this->assertMatchesRegularExpression(
            '/^Terlalu banyak percobaan\. Silakan coba lagi dalam \d+ detik\.$/',
            session('errors')->first('email'),
        );
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
