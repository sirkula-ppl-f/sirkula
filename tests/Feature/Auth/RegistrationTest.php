<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $override
     * @return array<string, string>
     */
    private function dataValid(array $override = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    public function test_halaman_registrasi_dapat_dibuka(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('Daftar Akun')
            ->assertSee('name="_token"', false)
            ->assertSee('autocomplete="name"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('dusk="name"', false)
            ->assertSee('dusk="email"', false)
            ->assertSee('dusk="password"', false)
            ->assertSee('dusk="password-confirmation"', false)
            ->assertSee('dusk="register-button"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-submit-once', false);
    }

    public function test_registrasi_berhasil_sebagai_warga_dengan_kata_sandi_ter_hash(): void
    {
        $response = $this->post('/register', $this->dataValid([
            'email' => '  Budi@Contoh.COM ',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::sole();

        $this->assertSame('budi@contoh.com', $user->email);
        $this->assertSame(UserRole::Warga, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_email_terdaftar_ditolak_termasuk_beda_huruf_kapital(): void
    {
        User::factory()->create(['email' => 'budi@contoh.com']);

        foreach (['budi@contoh.com', 'BUDI@Contoh.com'] as $email) {
            $response = $this->from('/register')->post('/register', $this->dataValid(['email' => $email]));

            $response->assertRedirect('/register')
                ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);
        }

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_field_role_dan_status_pada_request_diabaikan(): void
    {
        $this->post('/register', $this->dataValid([
            'role' => 'ADMIN',
            'status' => 'SUSPENDED',
        ]))->assertRedirect(route('dashboard'));

        $user = User::sole();

        $this->assertSame(UserRole::Warga, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
     */
    public static function dataTidakValid(): array
    {
        return [
            'nama kosong' => [['name' => ''], 'name', 'Nama wajib diisi.'],
            'nama lebih dari 100 karakter' => [['name' => str_repeat('a', 101)], 'name', 'Nama tidak boleh lebih dari 100 karakter.'],
            'email kosong' => [['email' => ''], 'email', 'Email wajib diisi.'],
            'email tidak valid' => [['email' => 'bukan-email'], 'email', 'Email harus berupa alamat email yang valid.'],
            'kata sandi kurang dari 8' => [['password' => 'abc123', 'password_confirmation' => 'abc123'], 'password', 'Kata sandi minimal terdiri dari 8 karakter.'],
            'kata sandi tanpa angka' => [['password' => 'rahasiaaja', 'password_confirmation' => 'rahasiaaja'], 'password', 'Kata sandi harus memuat minimal satu angka.'],
            'kata sandi tanpa huruf' => [['password' => '12345678', 'password_confirmation' => '12345678'], 'password', 'Kata sandi harus memuat minimal satu huruf.'],
            'konfirmasi tidak cocok' => [['password_confirmation' => 'berbeda123'], 'password', 'Konfirmasi kata sandi tidak cocok.'],
        ];
    }

    /**
     * @param  array<string, string>  $override
     */
    #[DataProvider('dataTidakValid')]
    public function test_validasi_per_field(array $override, string $field, string $pesan): void
    {
        $response = $this->from('/register')->post('/register', $this->dataValid($override));

        $response->assertRedirect('/register')
            ->assertSessionHasErrors([$field => $pesan]);

        foreach (array_diff(['name', 'email', 'password'], [$field]) as $lainnya) {
            $response->assertSessionDoesntHaveErrors($lainnya);
        }

        $this->assertGuest();
    }

    public function test_input_lama_disimpan_kecuali_kata_sandi(): void
    {
        $response = $this->from('/register')->post('/register', $this->dataValid([
            'password_confirmation' => 'berbeda123',
        ]));

        $response->assertSessionHasInput('name', 'Budi Santoso')
            ->assertSessionHasInput('email', 'budi@contoh.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
    }

    public function test_dashboard_menampilkan_nama_user_setelah_registrasi(): void
    {
        $this->post('/register', $this->dataValid());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('dusk="nav-user-name"', false);
    }

    public function test_nama_berisi_script_ditampilkan_ter_escape(): void
    {
        $raw = '<script>alert(1)</script>';

        $this->post('/register', $this->dataValid(['name' => $raw]))
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee(e($raw), false)
            ->assertDontSee($raw, false);
    }

    public function test_nama_tepat_100_karakter_diterima(): void
    {
        $this->post('/register', $this->dataValid(['name' => str_repeat('a', 100)]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
    }
}
