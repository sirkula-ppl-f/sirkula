<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MasterDataNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tag pembuka <a> yang memuat atribut tertentu, misalnya dusk="nav-categories".
     */
    private function tagTautan(string $html, string $atribut): string
    {
        $this->assertSame(1, preg_match('/<a\b[^>]*'.preg_quote($atribut, '/').'[^>]*>/', $html, $cocok), "Tautan dengan {$atribut} tidak ditemukan.");

        return $cocok[0];
    }

    public function test_admin_melihat_tautan_kategori_dan_material_di_navbar(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Beranda', 'Admin', 'Kategori', 'Material'])
            ->getContent();

        $kategori = $this->tagTautan($html, 'dusk="nav-categories"');
        $material = $this->tagTautan($html, 'dusk="nav-materials"');

        $this->assertStringContainsString('href="'.route('admin.categories.index').'"', $kategori);
        $this->assertStringContainsString('href="'.route('admin.materials.index').'"', $material);
        $this->assertStringNotContainsString('aria-current', $kategori);
        $this->assertStringNotContainsString('aria-current', $material);

        // Tautan lama tanpa key 'active' tetap memakai 'route'. Logo juga mengarah ke dashboard, jadi cek semua tag.
        preg_match_all('/<a\b[^>]*href="'.preg_quote(route('dashboard'), '/').'"[^>]*>/', $html, $cocok);
        $this->assertCount(1, array_filter($cocok[0], fn (string $tag) => str_contains($tag, 'aria-current="page"')), 'Tautan Beranda harus aktif di dashboard.');
    }

    public function test_warga_tidak_melihat_tautan_kategori_dan_material(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('dusk="nav-categories"', false)
            ->assertDontSee('dusk="nav-materials"', false)
            ->assertDontSee('href="'.route('admin.categories.index').'"', false)
            ->assertDontSee('href="'.route('admin.materials.index').'"', false);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string, 3: string}>
     */
    public static function halamanModul(): array
    {
        return [
            'daftar kategori' => ['admin.categories.index', null, 'nav-categories', 'nav-materials'],
            'tambah kategori' => ['admin.categories.create', null, 'nav-categories', 'nav-materials'],
            'ubah kategori' => ['admin.categories.edit', 'category', 'nav-categories', 'nav-materials'],
            'daftar material' => ['admin.materials.index', null, 'nav-materials', 'nav-categories'],
            'tambah material' => ['admin.materials.create', null, 'nav-materials', 'nav-categories'],
            'ubah material' => ['admin.materials.edit', 'material', 'nav-materials', 'nav-categories'],
        ];
    }

    #[DataProvider('halamanModul')]
    public function test_tautan_modul_aktif_di_semua_halaman_modulnya(string $route, ?string $model, string $navAktif, string $navLain): void
    {
        $parameter = match ($model) {
            'category' => Category::factory()->create(),
            'material' => Material::factory()->create(),
            null => [],
        };

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route($route, $parameter))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-current="page"', $this->tagTautan($html, 'dusk="'.$navAktif.'"'));
        $this->assertStringNotContainsString('aria-current', $this->tagTautan($html, 'dusk="'.$navLain.'"'));
        $this->assertStringNotContainsString('aria-current', $this->tagTautan($html, 'href="'.route('admin.dashboard').'"'));
    }
}
