<?php

namespace Tests\Browser;

use App\Models\Region;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilTest extends DuskTestCase
{
    public function test_warga_dapat_mengelola_profil_publik(): void
    {
        $user = User::factory()->create(['name' => 'Tania Putri']);
        $region = Region::create(['name' => 'Aceh', 'code' => '11']);

        $this->browse(function (Browser $browser) use ($user, $region) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->click('@nav-profil')
                ->waitForLocation('/profil')
                ->type('@name-input', 'Putri Tania')
                ->select('@region-input', (string) $region->id)
                ->click('@save-profile-button')
                ->waitFor('@alert')
                ->assertSeeIn('@alert', 'Profil publik berhasil diperbarui');
        });

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Putri Tania',
            'region_id' => $region->id,
        ]);
    }
}
