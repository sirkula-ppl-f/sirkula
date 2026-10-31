<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('profil.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_profile_page_can_be_rendered_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profil.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('profil.edit');
    }

    public function test_user_can_update_name_and_region(): void
    {
        $user = User::factory()->create();

        $region = Region::create([
            'name' => 'Jawa Timur',
            'code' => '35',
        ]);

        $response = $this->actingAs($user)->patch(route('profil.update'), [
            'name' => 'Putri Tania',
            'region_id' => $region->id,
        ]);

        $response->assertRedirect(route('profil.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Putri Tania',
            'region_id' => $region->id,
        ]);
    }

    public function test_validation_fails_if_name_or_region_is_missing_or_invalid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profil.update'), [
            'name' => '',
            'region_id' => 99999,
        ]);

        $response->assertSessionHasErrors(['name', 'region_id']);
    }
}
