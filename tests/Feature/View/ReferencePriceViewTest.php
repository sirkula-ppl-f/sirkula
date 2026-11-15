<?php

namespace Tests\Feature\View;

use App\Enums\ReferencePriceStatus;
use App\Models\Material;
use App\Models\ReferencePrice;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;
use Throwable;

/**
 * Komponen tampilan harga acuan (<x-reference-price.status-badge>, <x-reference-price.trend-chart>)
 * dan struktur aksesibilitas halaman publik.
 */
class ReferencePriceViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Harga tanpa disimpan ke database, untuk merender komponen grafik.
     *
     * @param  array<string, int>  $hargaPerTanggal  'Y-m-d' => harga
     * @return list<ReferencePrice>
     */
    private function hargaTanpaDb(array $hargaPerTanggal): array
    {
        $hasil = [];

        foreach ($hargaPerTanggal as $tanggal => $harga) {
            $hasil[] = new ReferencePrice(['effective_date' => $tanggal, 'price_per_kg' => $harga]);
        }

        return $hasil;
    }

    public function test_status_badge_menuliskan_tiap_status_sebagai_teks(): void
    {
        $harapan = [
            [ReferencePriceStatus::Current, 'Berlaku', 'bg-brand-50'],
            [ReferencePriceStatus::Scheduled, 'Terjadwal', 'bg-amber-50'],
            [ReferencePriceStatus::History, 'Riwayat', 'bg-gray-100'],
        ];

        foreach ($harapan as [$status, $label, $warna]) {
            $view = $this->blade('<x-reference-price.status-badge :status="$status" class="ms-1" />', ['status' => $status]);

            $view->assertSeeText($label)
                ->assertSee($warna, false)
                ->assertSee('whitespace-nowrap', false)
                ->assertSee('ms-1', false);

            foreach (array_diff(['Berlaku', 'Terjadwal', 'Riwayat'], [$label]) as $lain) {
                $view->assertDontSeeText($lain);
            }
        }
    }

    public function test_grafik_tren_memakai_id_kustom_dan_menggabungkan_class(): void
    {
        $html = (string) $this->blade(
            '<x-reference-price.trend-chart :prices="$a" title="Grafik A" id="grafik-a" class="mt-4" />'
            .'<x-reference-price.trend-chart :prices="$b" title="Grafik B" id="grafik-b" />',
            [
                'a' => $this->hargaTanpaDb(['2026-10-01' => 3000, '2026-11-01' => 3200]),
                'b' => $this->hargaTanpaDb(['2026-11-01' => 1500]),
            ],
        );

        foreach (['grafik-a', 'grafik-b'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="'.$id.'-title"'));
            $this->assertSame(1, substr_count($html, 'id="'.$id.'-desc"'));
            $this->assertStringContainsString('aria-labelledby="'.$id.'-title '.$id.'-desc"', $html);
        }

        $this->assertStringNotContainsString('id="trend-chart-title"', $html);
        $this->assertStringContainsString('<title id="grafik-a-title">Grafik A</title>', $html);
        $this->assertStringContainsString('<figure class="w-full max-w-104 mt-4">', $html);
        $this->assertSame(2, substr_count($html, 'dusk="trend-chart"'));
        $this->assertSame(1, substr_count($html, 'dusk="trend-chart-single"'));
    }

    public function test_grafik_tren_tanpa_data_ditolak(): void
    {
        try {
            $this->blade('<x-reference-price.trend-chart :prices="[]" title="Kosong" />');
            $this->fail('Grafik tanpa data seharusnya ditolak.');
        } catch (Throwable $e) {
            $penyebab = $e;
            while (! $penyebab instanceof InvalidArgumentException && $penyebab->getPrevious() !== null) {
                $penyebab = $penyebab->getPrevious();
            }

            $this->assertInstanceOf(InvalidArgumentException::class, $penyebab);
        }
    }

    public function test_aria_labelledby_dan_id_di_halaman_publik_konsisten(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 05:00:00', 'UTC'));

        $material = Material::factory()->create(['slug' => 'plastik-pet']);
        $provinsi = Region::create(['name' => 'Provinsi Alfa', 'code' => 'PA']);
        foreach (['2026-10-01', '2026-11-01'] as $tanggal) {
            ReferencePrice::factory()->forMaterial($material)->forRegion($provinsi)->effectiveOn($tanggal)->create();
        }

        foreach ([route('reference-prices.index'), route('reference-prices.show', [$material, $provinsi])] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/\sid="([^"]+)"/', $html, $id);
            $this->assertSame(array_unique($id[1]), $id[1], "Ada id ganda di {$url}.");

            preg_match_all('/aria-labelledby="([^"]+)"/', $html, $rujukan);
            foreach (preg_split('/\s+/', implode(' ', $rujukan[1]), -1, PREG_SPLIT_NO_EMPTY) as $target) {
                $this->assertContains($target, $id[1], "aria-labelledby merujuk id yang tidak ada: {$target} ({$url}).");
            }

            $this->assertSame(1, substr_count($html, '<h1'), "Halaman {$url} harus punya tepat satu h1.");
        }

        $this->get(route('reference-prices.show', [$material, $provinsi]))
            ->assertSeeInOrder(['<h1', '<h2 id="latest-price-title"', '<h2 id="trend-title"', '<h2 id="history-title"'], false);
    }
}
