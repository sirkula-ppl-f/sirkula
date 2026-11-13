<?php

namespace Tests\Feature\Admin;

use App\Models\Hub;
use App\Models\HubManager;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Region $region;

    private Hub $hub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->region = Region::create([
            'name' => 'Bandung',
            'code' => 'BDG',
        ]);

        $this->hub = Hub::create([
            'name' => 'Hub Bandung',
            'type' => 'BANK_SAMPAH',
            'region_id' => $this->region->id,
            'public_address' => 'Jl. Bandung No. 1',
            'description' => 'Hub pengelolaan sampah',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_admin_dapat_melihat_daftar_hub(): void
    {
        $this->get(route('admin.hubs.index'))
            ->assertOk()
            ->assertSee('Hub Bandung');
    }

    public function test_admin_dapat_membuka_form_tambah_hub(): void
    {
        $this->get(route('admin.hubs.create'))
            ->assertOk()
            ->assertSee('Nama Hub');
    }

    public function test_admin_dapat_menambahkan_hub(): void
    {
        $response = $this->post(route('admin.hubs.store'), [
            'name' => 'Hub Cimahi',
            'type' => 'RW',
            'region_id' => $this->region->id,
            'public_address' => 'Jl. Cimahi No. 2',
            'description' => 'Hub baru',
        ]);

        $hub = Hub::where('name', 'Hub Cimahi')->first();

        $this->assertNotNull($hub);
        $this->assertSame('ACTIVE', $hub->status);

        $response->assertRedirect(route('admin.hubs.show', $hub));
    }

    public function test_admin_dapat_melihat_detail_hub(): void
    {
        $this->get(route('admin.hubs.show', $this->hub))
            ->assertOk()
            ->assertSee('Hub Bandung');
    }

    public function test_admin_dapat_membuka_form_edit_hub(): void
    {
        $this->get(route('admin.hubs.edit', $this->hub))
            ->assertOk()
            ->assertSee('Hub Bandung')
            ->assertSee('Perbarui Hub');
    }

    public function test_admin_dapat_memperbarui_hub(): void
    {
        $response = $this->put(
            route('admin.hubs.update', $this->hub),
            [
                'name' => 'Hub Bandung Baru',
                'type' => 'KOMUNITAS',
                'region_id' => $this->region->id,
                'public_address' => 'Jl. Baru No. 5',
                'description' => 'Deskripsi diperbarui',
            ]
        );

        $this->assertDatabaseHas('hubs', [
            'id' => $this->hub->id,
            'name' => 'Hub Bandung Baru',
            'type' => 'KOMUNITAS',
        ]);

        $response->assertRedirect(
            route('admin.hubs.show', $this->hub)
        );
    }

    public function test_admin_dapat_mengarsipkan_hub(): void
    {
        $response = $this->patch(
            route('admin.hubs.archive', $this->hub)
        );

        $this->assertDatabaseHas('hubs', [
            'id' => $this->hub->id,
            'status' => 'ARCHIVED',
        ]);

        $response->assertRedirect(route('admin.hubs.index'));
    }

    public function test_admin_dapat_menugaskan_pengelola_hub(): void
    {
        $user = User::factory()->create();

        $response = $this->post(
            route('admin.hubs.managers.store', $this->hub),
            ['user_id' => $user->id]
        );

        $this->assertDatabaseHas('hub_managers', [
            'hub_id' => $this->hub->id,
            'user_id' => $user->id,
            'assignment_status' => 'ACTIVE',
        ]);

        $response->assertRedirect(
            route('admin.hubs.show', $this->hub)
        );
    }

    public function test_warga_tidak_dapat_mengakses_pengelolaan_hub(): void
    {
        $warga = User::factory()->create();

        $this->actingAs($warga);

        $routes = [
            ['GET', route('admin.hubs.index')],
            ['GET', route('admin.hubs.create')],
            ['POST', route('admin.hubs.store')],
            ['GET', route('admin.hubs.show', $this->hub)],
            ['GET', route('admin.hubs.edit', $this->hub)],
            ['PUT', route('admin.hubs.update', $this->hub)],
            ['PATCH', route('admin.hubs.archive', $this->hub)],
            ['POST', route('admin.hubs.managers.store', $this->hub)],
        ];

        foreach ($routes as [$method, $url]) {
            $this->call($method, $url)->assertForbidden();
        }
    }

    public function test_archive_hub_menonaktifkan_semua_pengelola_aktif(): void
    {
        $user = User::factory()->create();

        $manager = HubManager::create([
            'hub_id' => $this->hub->id,
            'user_id' => $user->id,
            'assignment_status' => 'ACTIVE',
            'assigned_at' => now(),
        ]);

        $this->patch(route('admin.hubs.archive', $this->hub))
            ->assertRedirect(route('admin.hubs.index'));

        $this->assertDatabaseHas('hub_managers', [
            'id' => $manager->id,
            'assignment_status' => 'INACTIVE',
        ]);

        $manager->refresh();

        $this->assertNotNull($manager->deactivated_at);
    }

    public function test_admin_dapat_menonaktifkan_pengelola_hub(): void
    {
        $user = User::factory()->create();

        $manager = HubManager::create([
            'hub_id' => $this->hub->id,
            'user_id' => $user->id,
            'assignment_status' => 'ACTIVE',
            'assigned_at' => now(),
        ]);

        $this->patch(
            route('admin.hubs.managers.deactivate', [
                $this->hub,
                $manager,
            ])
        )->assertRedirect(route('admin.hubs.show', $this->hub));

        $this->assertDatabaseHas('hub_managers', [
            'id' => $manager->id,
            'assignment_status' => 'INACTIVE',
        ]);

        $this->assertNotNull($manager->fresh()->deactivated_at);
    }

    public function test_admin_tidak_dapat_menugaskan_pengelola_pada_hub_arsip(): void
    {
        $this->hub->update([
            'status' => 'ARCHIVED',
        ]);

        $user = User::factory()->create();

        $this->post(
            route('admin.hubs.managers.store', $this->hub),
            ['user_id' => $user->id]
        )->assertSessionHasErrors('hub');

        $this->assertDatabaseMissing('hub_managers', [
            'hub_id' => $this->hub->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_tidak_dapat_menambahkan_hub_dengan_data_tidak_valid(): void
    {
        $this->from(route('admin.hubs.create'))
            ->post(route('admin.hubs.store'), [
                'name' => '',
                'type' => 'INVALID_TYPE',
                'region_id' => 999999,
                'public_address' => '',
            ])
            ->assertRedirect(route('admin.hubs.create'))
            ->assertSessionHasErrors([
                'name',
                'type',
                'region_id',
                'public_address',
            ]);

        $this->assertDatabaseMissing('hubs', [
            'name' => '',
        ]);
    }

    public function test_admin_tidak_dapat_menonaktifkan_pengelola_milik_hub_lain(): void
    {
        $user = User::factory()->create();

        $manager = HubManager::create([
            'hub_id' => $this->hub->id,
            'user_id' => $user->id,
            'assignment_status' => 'ACTIVE',
            'assigned_at' => now(),
        ]);

        $anotherHub = Hub::create([
            'name' => 'Hub Cimahi',
            'type' => 'RW',
            'region_id' => $this->region->id,
            'public_address' => 'Jl. Cimahi No. 2',
            'description' => 'Hub lain',
            'status' => 'ACTIVE',
        ]);

        $this->patch(
            route('admin.hubs.managers.deactivate', [
                $anotherHub,
                $manager,
            ])
        )->assertNotFound();

        $this->assertDatabaseHas('hub_managers', [
            'id' => $manager->id,
            'assignment_status' => 'ACTIVE',
        ]);
    }
}
