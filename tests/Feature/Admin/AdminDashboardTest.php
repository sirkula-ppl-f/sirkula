<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Guide;
use App\Models\Hub;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_admin_menampilkan_statistik_operasional_dari_controller(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        User::factory()->suspended()->create();

        Category::factory()->count(2)->create(['is_active' => true]);
        Category::factory()->inactive()->create();

        $activeMaterial = Material::factory()->create(['is_active' => true]);
        Material::factory()->count(2)->create(['is_active' => true]);
        Material::factory()->inactive()->create();

        $region = Region::create([
            'name' => 'Bandung',
            'code' => 'BDG',
        ]);

        Hub::create([
            'name' => 'Hub Aktif',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Aktif No. 1',
            'status' => 'ACTIVE',
        ]);

        Hub::create([
            'name' => 'Hub Arsip',
            'type' => 'BANK_SAMPAH',
            'region_id' => $region->id,
            'public_address' => 'Jl. Arsip No. 2',
            'status' => 'ARCHIVED',
        ]);

        ReferencePrice::factory()->count(4)->create([
            'material_id' => $activeMaterial->id,
            'region_id' => $region->id,
            'published_by' => $admin->id,
        ]);

        Guide::factory()->count(2)->create(['status' => 'published']);
        Guide::factory()->draft()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', [
                'categories_active' => 2,
                'materials_active' => 3,
                'hubs_active' => 1,
                'reference_prices_total' => 4,
                'guides_published' => 2,
                'users_total' => 4,
                'users_suspended' => 1,
                'listings_hidden' => 0,
            ])
            ->assertSee('2 kategori')
            ->assertSee('3 jenis material')
            ->assertSee('1 beroperasi')
            ->assertSee('4 entri')
            ->assertSee('2 terbit')
            ->assertSee('4 akun')
            ->assertSee('1 akun ditangguhkan')
            ->assertSee('0 dinonaktifkan');
    }
}
