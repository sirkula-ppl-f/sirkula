<?php

namespace Tests\Browser;

use App\Enums\UserStatus;
use App\Models\User;
use Facebook\WebDriver\Chrome\ChromeDevToolsDriver;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminUserManagementTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_admin_menangguhkan_dan_mengaktifkan_kembali_akun_pengguna_lewat_dialog(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Sirkula',
            'email' => 'admin@sirkula.test',
        ]);

        $warga = User::factory()->create([
            'name' => 'Dewi Anggraini',
            'email' => 'dewi@sirkula.test',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $warga) {
            $browser->resize(1280, 800)
                ->loginAs($admin)
                ->visit(route('admin.users.index'))
                ->assertSee('Kelola Status Akun')
                ->assertSee('Dewi Anggraini')
                ->click("@select-user-{$warga->id}")
                ->assertSeeIn('@panel-user-name', 'Dewi Anggraini')
                ->assertSeeIn('@panel-user-status', 'Aktif')
                ->type('@reason-input', 'Pelanggaran ketentuan komunitas berulang.')
                ->click('@panel-action-button')
                ->waitFor('@confirm-status-dialog')
                ->assertSeeIn('@confirm-status-dialog', 'Tangguhkan akun Dewi Anggraini?')
                ->click('@dialog-confirm-button')
                ->waitFor('@alert')
                ->assertSeeIn('@alert', 'Akun pengguna Dewi Anggraini berhasil ditangguhkan.')
                ->assertSeeIn('@panel-user-status', 'Ditangguhkan');

            $this->assertSame(UserStatus::Suspended, $warga->fresh()->status);

            // Aktifkan kembali akun yang baru ditangguhkan
            $browser->type('@reason-input', 'Pengguna telah melakukan klarifikasi ke admin.')
                ->click('@panel-action-button')
                ->waitFor('@confirm-status-dialog')
                ->assertSeeIn('@confirm-status-dialog', 'Aktifkan kembali akun Dewi Anggraini?')
                ->click('@dialog-confirm-button')
                ->waitFor('@alert')
                ->assertSeeIn('@alert', 'Akun pengguna Dewi Anggraini berhasil diaktifkan kembali.')
                ->assertSeeIn('@panel-user-status', 'Aktif');

            $this->assertSame(UserStatus::Active, $warga->fresh()->status);
        });
    }

    public function test_filter_chip_dan_pencarian_berfungsi_serta_responsif_di_lebar_360px(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        User::factory()->create(['name' => 'Rina Sari', 'email' => 'rina@sirkula.test']);
        User::factory()->suspended()->create(['name' => 'Budi Hartono', 'email' => 'budi@sirkula.test']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->resize(1280, 800)
                ->loginAs($admin)
                ->visit(route('admin.users.index'))
                ->click('@filter-suspended')
                ->waitForLocation('/admin/users')
                ->assertSee('Budi Hartono')
                ->assertDontSee('rina@sirkula.test')
                ->click('@filter-all')
                ->waitForLocation('/admin/users')
                ->type('@search-input', 'Rina')
                ->click('@search-submit')
                ->waitForLocation('/admin/users')
                ->assertSee('Rina Sari')
                ->assertDontSee('Budi Hartono');

            // Cek responsivitas 360px tanpa geser horizontal (NFR-002)
            $devTools = new ChromeDevToolsDriver($browser->driver);
            $devTools->execute('Emulation.setDeviceMetricsOverride', [
                'width' => 360,
                'height' => 740,
                'deviceScaleFactor' => 1,
                'mobile' => true,
            ]);

            try {
                $browser->visit(route('admin.users.index'));

                [$scrollWidth, $innerWidth] = $browser->script(
                    'return [document.documentElement.scrollWidth, window.innerWidth];'
                )[0];

                $this->assertLessThanOrEqual($innerWidth, $scrollWidth);
            } finally {
                $devTools->execute('Emulation.clearDeviceMetricsOverride');
            }
        });
    }
}
