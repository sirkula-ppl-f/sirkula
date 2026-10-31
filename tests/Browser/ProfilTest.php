<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_warga_dapat_mengelola_profil_publik(): void
    {
        $user = User::factory()->create([
            'name' => 'Tania Putri',
            'email' => 'tania@contoh.com',
            'password' => 'rahasia123',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->click('@nav-profil')
                ->waitForLocation('/profil')
                ->type('@name-input', 'Putri Tania')
                ->type('@region-input', 'Aceh')
                ->click('@save-profile-button')
                ->waitFor('@alert')
                ->assertSeeIn('@alert', 'Profil berhasil diperbarui');
        });
    }
}
