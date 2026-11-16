<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferencePriceNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tag pembuka <a> yang memuat atribut tertentu.
     */
    private function tagTautan(string $html, string $atribut): string
    {
        $this->assertSame(1, preg_match('/<a\b[^>]*'.preg_quote($atribut, '/').'[^>]*>/', $html, $cocok), "Tautan dengan {$atribut} tidak ditemukan.");

        return $cocok[0];
    }

    public function test_admin_melihat_tautan_kelola_harga_acuan_setelah_material(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Kategori', 'Material', 'Kelola Harga Acuan', 'Kelola Akun'])
            ->getContent();

        $tautan = $this->tagTautan($html, 'dusk="nav-admin-reference-prices"');

        $this->assertStringContainsString('href="'.route('admin.reference-prices.index').'"', $tautan);
        $this->assertStringNotContainsString('aria-current', $tautan);
    }

    public function test_tautan_admin_aktif_di_halaman_riwayat_dan_terbitkan(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['admin.reference-prices.index', 'admin.reference-prices.create'] as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('aria-current="page"', $this->tagTautan($html, 'dusk="nav-admin-reference-prices"'));
            $this->assertStringNotContainsString('aria-current', $this->tagTautan($html, 'dusk="nav-materials"'));
        }
    }

    public function test_dashboard_admin_punya_kartu_harga_acuan(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Material', 'Harga Acuan', 'Terbitkan harga acuan material per provinsi dan lihat riwayatnya.', 'Buka Harga Acuan', 'Kelola Akun'])
            ->getContent();

        $this->assertStringContainsString('href="'.route('admin.reference-prices.index').'"', $this->tagTautan($html, 'dusk="reference-prices-card"'));
    }

    public function test_warga_dan_tamu_tidak_melihat_tautan_kelola_harga_acuan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('dusk="nav-admin-reference-prices"', false)
            ->assertDontSee('Kelola Harga Acuan')
            ->assertDontSee(route('admin.reference-prices.index'), false);

        auth()->logout();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('dusk="nav-admin-reference-prices"', false)
            ->assertDontSee(route('admin.reference-prices.index'), false);
    }
}
