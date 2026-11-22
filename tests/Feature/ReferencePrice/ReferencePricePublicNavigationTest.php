<?php

namespace Tests\Feature\ReferencePrice;

use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tautan "Harga Acuan" (halaman publik) di navbar.
 *
 * Bukti tautan memakai atribut dusk dan href, bukan teks, karena "Kelola Harga Acuan" (admin)
 * juga memuat teks "Harga Acuan".
 */
class ReferencePricePublicNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tag pembuka <a> dengan atribut dusk tertentu. Gagal bila tidak ada atau lebih dari satu.
     */
    private function tagTautan(string $html, string $dusk): string
    {
        $jumlah = preg_match_all('/<a\b[^>]*dusk="'.preg_quote($dusk, '/').'"[^>]*>/', $html, $cocok);
        $this->assertSame(1, $jumlah, "Harus ada tepat satu tautan dusk=\"{$dusk}\".");

        return $cocok[0][0];
    }

    private function assertTautanPublik(string $html, bool $aktif): void
    {
        $tag = $this->tagTautan($html, 'nav-reference-prices');

        $this->assertStringContainsString('href="'.route('reference-prices.index').'"', $tag);

        if ($aktif) {
            $this->assertStringContainsString('aria-current="page"', $tag);
        } else {
            $this->assertStringNotContainsString('aria-current', $tag);
        }
    }

    private function posisi(string $html, string $teks): int
    {
        $posisi = strpos($html, $teks);
        $this->assertNotFalse($posisi, "{$teks} tidak ditemukan.");

        return $posisi;
    }

    private function detailUrl(): string
    {
        $harga = ReferencePrice::factory()
            ->forMaterial(Material::factory()->create())
            ->forRegion(Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']))
            ->create();

        return route('reference-prices.show', ['material' => $harga->material, 'region' => $harga->region]);
    }

    public function test_tamu_melihat_tautan_setelah_katalog(): void
    {
        $html = (string) $this->blade('<x-layouts.app>Isi</x-layouts.app>');

        $this->assertTautanPublik($html, aktif: false);
        $this->assertLessThan(
            $this->posisi($html, 'dusk="nav-reference-prices"'),
            $this->posisi($html, 'href="'.route('catalog.index').'"'),
            'Tautan Harga Acuan harus berada setelah Katalog.'
        );
        $this->assertStringNotContainsString('dusk="nav-admin-reference-prices"', $html);
    }

    public function test_tamu_tautan_aktif_di_daftar_dan_detail_publik(): void
    {
        foreach ([route('reference-prices.index'), $this->detailUrl()] as $url) {
            $this->assertTautanPublik($this->get($url)->assertOk()->getContent(), aktif: true);
        }
    }

    public function test_warga_melihat_tautan_dan_aktif_di_halaman_publik(): void
    {
        $warga = User::factory()->create();

        $html = $this->actingAs($warga)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertTautanPublik($html, aktif: false);
        $this->assertLessThan($this->posisi($html, 'dusk="nav-reference-prices"'), $this->posisi($html, 'dusk="nav-profil"'));
        $this->assertStringNotContainsString('dusk="nav-admin-reference-prices"', $html);

        $this->assertTautanPublik($this->actingAs($warga)->get(route('reference-prices.index'))->getContent(), aktif: true);
        $this->assertTautanPublik($this->actingAs($warga)->get($this->detailUrl())->assertOk()->getContent(), aktif: true);
    }

    public function test_admin_melihat_tautan_publik_dan_tautan_kelola(): void
    {
        $admin = User::factory()->admin()->create();

        // Di tampilan warga (/dashboard), admin melihat tautan Harga Acuan publik dan tombol ke Panel Admin.
        $wargaHtml = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertTautanPublik($wargaHtml, aktif: false);
        $this->assertLessThan($this->posisi($wargaHtml, 'href="'.route('admin.dashboard').'"'), $this->posisi($wargaHtml, 'dusk="nav-reference-prices"'));

        // Di Panel Admin (/admin), admin melihat tautan Kelola Harga Acuan di sidebar sesuai urutan seksi.
        $adminHtml = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('admin.reference-prices.index').'"', $this->tagTautan($adminHtml, 'nav-admin-reference-prices'));
        $this->assertLessThan($this->posisi($adminHtml, 'dusk="nav-admin-reference-prices"'), $this->posisi($adminHtml, 'dusk="nav-hubs"'));
        $this->assertLessThan($this->posisi($adminHtml, 'dusk="nav-users"'), $this->posisi($adminHtml, 'dusk="nav-admin-reference-prices"'));
    }

    public function test_di_halaman_admin_hanya_tautan_kelola_yang_aktif(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['admin.reference-prices.index', 'admin.reference-prices.create'] as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('dusk="nav-reference-prices"', $html);
            $this->assertStringContainsString('aria-current="page"', $this->tagTautan($html, 'nav-admin-reference-prices'));
        }

        $html = $this->actingAs($admin)->get(route('reference-prices.index'))->assertOk()->getContent();

        $this->assertTautanPublik($html, aktif: true);
        $this->assertStringNotContainsString('dusk="nav-admin-reference-prices"', $html);
    }

    public function test_navbar_katalog_menaut_ke_halaman_harga_acuan(): void
    {
        $html = $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('<a href="#">Harga Acuan</a>', false)
            ->getContent();

        $this->assertTautanPublik($html, aktif: false);
    }
}
