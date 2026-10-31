<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dan_warga_tidak_bisa_mengakses_pengelolaan_pengguna(): void
    {
        $target = User::factory()->create();

        // Tamu diarahkan ke halaman login
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->post(route('admin.users.suspend', $target), ['reason' => 'Pelanggaran aturan'])->assertRedirect(route('login'));
        $this->post(route('admin.users.activate', $target), ['reason' => 'Banding diterima'])->assertRedirect(route('login'));

        // Warga mendapat 403 Forbidden
        $warga = User::factory()->create();

        $this->actingAs($warga)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($warga)->post(route('admin.users.suspend', $target), ['reason' => 'Pelanggaran aturan'])->assertForbidden();
        $this->actingAs($warga)->post(route('admin.users.activate', $target), ['reason' => 'Banding diterima'])->assertForbidden();
    }

    public function test_admin_dapat_melihat_daftar_pengguna_serta_mencari_dan_memfilter_status(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        $wargaAktif = User::factory()->create(['name' => 'Dewi Anggraini', 'email' => 'dewi@sirkula.test']);
        $wargaDitangguhkan = User::factory()->suspended()->create(['name' => 'Budi Hartono', 'email' => 'budi@sirkula.test']);

        // Tanpa filter: semua tampil
        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Dewi Anggraini')
            ->assertSee('Budi Hartono');

        // Filter status ACTIVE
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => UserStatus::Active->value]))
            ->assertOk()
            ->assertSee('Dewi Anggraini')
            ->assertDontSee('budi@sirkula.test');

        // Filter status SUSPENDED
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => UserStatus::Suspended->value]))
            ->assertOk()
            ->assertSee('Budi Hartono')
            ->assertDontSee('dewi@sirkula.test');

        // Pencarian nama atau email
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'dewi@sirkula']))
            ->assertOk()
            ->assertSee('Dewi Anggraini')
            ->assertDontSee('Budi Hartono');

        // Filter tidak valid atau search berupa array di URL diabaikan tanpa error 500
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'TIDAK_VALID', 'search' => ['a', 'b']]))
            ->assertOk()
            ->assertSee('Dewi Anggraini')
            ->assertSee('Budi Hartono');
    }

    public function test_admin_berhasil_menangguhkan_akun_warga_mencabut_sesi_dan_mencatat_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $warga = User::factory()->create([
            'name' => 'Agus Pratama',
            'remember_token' => 'token-lama',
        ]);

        // Simulasikan sesi aktif milik warga di tabel sessions
        DB::table(config('session.table', 'sessions'))->insert([
            'id' => 'sesi-warga-123',
            'user_id' => $warga->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $warga), [
                'reason' => 'Terbukti melakukan spam listing berulang kali.',
            ]);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', "Akun pengguna {$warga->name} berhasil ditangguhkan.");

        $warga->refresh();
        $this->assertSame(UserStatus::Suspended, $warga->status);
        $this->assertNotSame('token-lama', $warga->remember_token);

        // Sesi warga di database harus terhapus
        $this->assertDatabaseMissing(config('session.table', 'sessions'), [
            'id' => 'sesi-warga-123',
        ]);

        // Audit log harus tercatat
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'USER_SUSPENDED',
            'entity_type' => User::class,
            'entity_id' => $warga->id,
            'result' => 'SUCCESS',
            'reason' => 'Terbukti melakukan spam listing berulang kali.',
        ]);
    }

    public function test_validasi_alasan_penangguhan_dan_pengaktifan_wajib_diisi(): void
    {
        $admin = User::factory()->admin()->create();
        $wargaAktif = User::factory()->create();
        $wargaSuspended = User::factory()->suspended()->create();

        // Alasan kosong saat suspend
        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $wargaAktif), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        // Alasan kurang dari 5 karakter saat suspend
        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $wargaAktif), ['reason' => 'abc'])
            ->assertSessionHasErrors('reason');

        $this->assertSame(UserStatus::Active, $wargaAktif->fresh()->status);

        // Alasan kosong saat activate
        $this->actingAs($admin)
            ->post(route('admin.users.activate', $wargaSuspended), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(UserStatus::Suspended, $wargaSuspended->fresh()->status);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_admin_tidak_bisa_menangguhkan_dirinya_sendiri(): void
    {
        $admin1 = User::factory()->admin()->create();
        User::factory()->admin()->create(); // admin aktif kedua

        $this->actingAs($admin1)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $admin1), [
                'reason' => 'Mencoba menangguhkan diri sendiri.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Anda tidak dapat menangguhkan akun Anda sendiri.');

        $this->assertSame(UserStatus::Active, $admin1->fresh()->status);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_admin_aktif_terakhir_tidak_bisa_ditangguhkan_tetapi_bisa_bila_ada_admin_aktif_lain(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        // Ada 2 admin aktif -> admin1 boleh menangguhkan admin2
        $this->actingAs($admin1)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $admin2), [
                'reason' => 'Penonaktifan akun admin kedua untuk pemeriksaan.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame(UserStatus::Suspended, $admin2->fresh()->status);

        // Uji guard last-admin jika suatu kondisi membuat admin mencoba men-suspend satu-satunya admin aktif
        $admin2 = $admin2->fresh();
        $admin2->forceFill(['status' => UserStatus::Active])->save();
        User::whereKey($admin1->id)->update(['status' => UserStatus::Suspended->value]);

        // Kini hanya ada 1 admin aktif di database (admin2), sementara admin1 di memori masih berstatus Active agar lolos middleware
        $this->actingAs($admin1)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $admin2), [
                'reason' => 'Mencoba menangguhkan admin aktif terakhir.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Tidak dapat menangguhkan admin aktif terakhir di sistem.');

        $this->assertSame(UserStatus::Active, $admin2->fresh()->status);
    }

    public function test_admin_berhasil_mengaktifkan_kembali_akun_yang_ditangguhkan_dan_mencatat_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $warga = User::factory()->suspended()->create(['name' => 'Budi Hartono']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.activate', $warga), [
                'reason' => 'Klarifikasi diterima dan pengguna berjanji mematuhi aturan.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', "Akun pengguna {$warga->name} berhasil diaktifkan kembali.");

        $this->assertSame(UserStatus::Active, $warga->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'USER_ACTIVATED',
            'entity_type' => User::class,
            'entity_id' => $warga->id,
            'result' => 'SUCCESS',
            'reason' => 'Klarifikasi diterima dan pengguna berjanji mematuhi aturan.',
        ]);
    }

    public function test_menangguhkan_akun_yang_sudah_ditangguhkan_atau_mengaktifkan_akun_aktif_ditolak(): void
    {
        $admin = User::factory()->admin()->create();
        $wargaAktif = User::factory()->create();
        $wargaSuspended = User::factory()->suspended()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $wargaSuspended), [
                'reason' => 'Sudah ditangguhkan sebelumnya.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun pengguna ini sudah dalam status ditangguhkan.');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.activate', $wargaAktif), [
                'reason' => 'Sudah aktif sebelumnya.',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun pengguna ini sudah berstatus aktif.');

        $this->assertSame(0, AuditLog::count());
    }
}
