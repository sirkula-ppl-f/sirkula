<?php

namespace Tests\Browser;

use App\Models\Hub;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HubManagementTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function buatWilayah(): Region
    {
        return Region::create([
            'name' => 'Bandung',
            'code' => 'BDG',
        ]);
    }

    private function buatHub(Region $region, array $attributes = []): Hub
    {
        return Hub::create(array_merge([
            'name' => 'Hub Bandung',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Bandung No. 1',
            'description' => 'Hub pengelolaan sampah',
            'status' => 'ACTIVE',
        ], $attributes));
    }

    public function test_admin_dapat_melihat_daftar_dan_mencari_hub(): void
    {
        $admin = User::factory()->admin()->create();
        $region = $this->buatWilayah();

        $this->buatHub($region);

        $this->buatHub($region, [
            'name' => 'Hub Cimahi',
            'public_address' => 'Jl. Cimahi No. 2',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/hubs')
                ->assertSee('Manajemen Hub')
                ->assertSee('Hub Bandung')
                ->assertSee('Hub Cimahi')
                ->type('input[name="search"]', 'Hub Bandung')
                ->click('form[action="'.route('admin.hubs.index').'"] button[type="submit"]')
                ->waitForText('Hub Bandung')
                ->assertDontSee('Hub Cimahi');
        });
    }

    public function test_admin_dapat_membuat_hub_melalui_browser(): void
    {
        $admin = User::factory()->admin()->create();
        $region = $this->buatWilayah();

        $this->browse(function (Browser $browser) use ($admin, $region) {
            $browser->loginAs($admin)
                ->visit('/admin/hubs/create')
                ->assertSee('Tambah Hub')
                ->type('input[name="name"]', 'Hub Daur Ulang')
                ->select('select[name="type"]', 'RW')
                ->select('select[name="region_id"]', (string) $region->id)
                ->type('textarea[name="public_address"]', 'Jl. Daur Ulang No. 10')
                ->type('textarea[name="description"]', 'Hub hasil pengujian Dusk')
                ->click('form[action="'.route('admin.hubs.store').'"] button[type="submit"]')
                ->waitForText('Hub berhasil ditambahkan.')
                ->assertSee('Hub Daur Ulang');
        });

        $this->assertDatabaseHas('hubs', [
            'name' => 'Hub Daur Ulang',
            'type' => 'RW',
            'region_id' => $region->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_dapat_mengubah_informasi_hub_melalui_browser(): void
    {
        $admin = User::factory()->admin()->create();
        $region = $this->buatWilayah();
        $hub = $this->buatHub($region);

        $this->browse(function (Browser $browser) use ($admin, $region, $hub) {
            $browser->loginAs($admin)
                ->visit('/admin/hubs/'.$hub->id.'/edit')
                ->assertSee('Edit Hub')
                ->clear('input[name="name"]')
                ->type('input[name="name"]', 'Hub Bandung Diperbarui')
                ->select('select[name="type"]', 'KOMUNITAS')
                ->select('select[name="region_id"]', (string) $region->id)
                ->clear('textarea[name="public_address"]')
                ->type('textarea[name="public_address"]', 'Jl. Baru No. 15')
                ->clear('textarea[name="description"]')
                ->type('textarea[name="description"]', 'Deskripsi terbaru')
                ->click('form[action="'.route('admin.hubs.update', $hub).'"] button[type="submit"]')
                ->waitForText('Hub berhasil diperbarui.')
                ->assertSee('Hub Bandung Diperbarui');
        });

        $this->assertDatabaseHas('hubs', [
            'id' => $hub->id,
            'name' => 'Hub Bandung Diperbarui',
            'type' => 'KOMUNITAS',
            'public_address' => 'Jl. Baru No. 15',
            'description' => 'Deskripsi terbaru',
        ]);
    }

    public function test_admin_dapat_mengarsipkan_hub_melalui_browser(): void
    {
        $admin = User::factory()->admin()->create();
        $region = $this->buatWilayah();
        $hub = $this->buatHub($region);

        $this->browse(function (Browser $browser) use ($admin, $hub) {
            $browser->loginAs($admin)
                ->visit('/admin/hubs')
                ->assertSee('Hub Bandung')
                ->click('form[action="'.route('admin.hubs.archive', $hub).'"] button')
                ->acceptDialog()
                ->waitForText('Hub berhasil diarsipkan.');
        });

        $this->assertDatabaseHas('hubs', [
            'id' => $hub->id,
            'status' => 'ARCHIVED',
        ]);
    }
}
