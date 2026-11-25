<?php

namespace Tests\Feature\Admin;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-06: menu "Moderasi Listing" di sidebar admin dan kartu di dashboard admin.
 */
class ListingModerationNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tag pembuka tautan sidebar Moderasi Listing di halaman ini.
     */
    private function tautanSidebar(string $html): string
    {
        $this->assertSame(1, preg_match('/<a\b[^>]*dusk="nav-admin-listings"[^>]*>/s', $html, $cocok), 'Tautan sidebar Moderasi Listing tidak ditemukan.');

        return $cocok[0];
    }

    public function test_sidebar_admin_memuat_section_moderasi_setelah_konten_dan_referensi(): void
    {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Utama', 'Dashboard', 'Data Master', 'Konten & Referensi', 'Panduan & Edukasi', 'Moderasi', 'Moderasi Listing', 'Pengguna', 'Kelola Akun'])
            ->getContent();

        $tautan = $this->tautanSidebar($html);
        $this->assertStringContainsString('href="'.route('admin.listings.index').'"', $tautan);
        $this->assertStringNotContainsString('aria-current', $tautan);
    }

    public function test_tautan_sidebar_aktif_di_semua_halaman_moderasi_listing(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->create();

        foreach ([route('admin.listings.index'), route('admin.listings.show', $listing), route('admin.listings.moderations')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('aria-current="page"', $this->tautanSidebar($html), "Tautan sidebar harus aktif di {$url}.");
        }
    }

    public function test_kartu_dashboard_menampilkan_jumlah_listing_yang_dinonaktifkan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dusk="listing-moderation-card"', false)
            ->assertSee('href="'.route('admin.listings.index').'"', false)
            ->assertSeeInOrder(['0 dinonaktifkan', 'Moderasi Listing', 'Cari listing, nonaktifkan listing bermasalah', 'Buka Moderasi Listing']);

        Listing::factory()->count(2)->dinonaktifkanAdmin()->create();
        Listing::factory()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('2 dinonaktifkan');
    }

    public function test_warga_tidak_melihat_menu_dan_kartu_moderasi_listing(): void
    {
        $warga = User::factory()->create();

        $this->actingAs($warga)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('dusk="nav-admin-listings"', false)
            ->assertDontSee('href="'.route('admin.listings.index').'"', false);

        $this->actingAs($warga)->get('/admin')->assertForbidden();

        // Admin di tampilan warga juga tidak melihat sidebar admin.
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('dusk="nav-admin-listings"', false);
    }
}
