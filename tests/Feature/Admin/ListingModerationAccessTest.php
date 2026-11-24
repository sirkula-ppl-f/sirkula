<?php

namespace Tests\Feature\Admin;

use App\Models\Hub;
use App\Models\HubManager;
use App\Models\Listing;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-06: halaman dan aksi moderasi listing hanya untuk admin aktif.
 */
class ListingModerationAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ALASAN = 'Listing dilaporkan tidak sesuai dengan foto.';

    private Listing $tampil;

    private Listing $disembunyikan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tampil = Listing::factory()->create();
        $this->disembunyikan = Listing::factory()->dinonaktifkanAdmin()->create();
    }

    /**
     * @return list<string>
     */
    private function halaman(): array
    {
        return [
            route('admin.listings.index'),
            route('admin.listings.moderations'),
            route('admin.listings.show', $this->tampil),
        ];
    }

    private function assertTidakAdaPerubahan(): void
    {
        $this->assertSame('VISIBLE', $this->tampil->fresh()->moderation_status->value);
        $this->assertSame('HIDDEN', $this->disembunyikan->fresh()->moderation_status->value);
        $this->assertDatabaseCount('listing_moderations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function pengelolaHub(): User
    {
        $region = Region::create(['name' => 'Provinsi Contoh', 'code' => 'PC']);
        $hub = Hub::create([
            'name' => 'Bank Sampah Contoh',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Contoh No. 1',
            'status' => 'ACTIVE',
        ]);
        $pengelola = User::factory()->create();
        HubManager::create(['hub_id' => $hub->id, 'user_id' => $pengelola->id, 'assignment_status' => 'ACTIVE', 'assigned_at' => now()]);

        return $pengelola;
    }

    public function test_admin_dapat_membuka_semua_halaman_moderasi(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->halaman() as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_warga_dan_pengelola_hub_mendapat_403_dan_tidak_bisa_memoderasi(): void
    {
        foreach (['warga' => User::factory()->create(), 'pengelola hub' => $this->pengelolaHub()] as $peran => $user) {
            foreach ($this->halaman() as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }

            $this->actingAs($user)
                ->patch(route('admin.listings.deactivate', $this->tampil), ['reason' => self::ALASAN])
                ->assertForbidden();
            $this->actingAs($user)
                ->patch(route('admin.listings.restore', $this->disembunyikan), ['reason' => self::ALASAN])
                ->assertForbidden();
        }

        $this->assertTidakAdaPerubahan();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        foreach ($this->halaman() as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->patch(route('admin.listings.deactivate', $this->tampil), ['reason' => self::ALASAN])
            ->assertRedirect(route('login'));

        $this->assertTidakAdaPerubahan();
    }

    public function test_admin_yang_ditangguhkan_diarahkan_ke_login(): void
    {
        $admin = User::factory()->admin()->suspended()->create();

        $this->actingAs($admin)->get(route('admin.listings.index'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($admin)
            ->patch(route('admin.listings.deactivate', $this->tampil), ['reason' => self::ALASAN])
            ->assertRedirect(route('login'));

        $this->assertTidakAdaPerubahan();
    }
}
