<?php

namespace Tests\Feature;

use App\Models\Guide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pencatatan tayang dan interaksi, lokasi tampil, dan komponen cuplikan panduan (PBI-18).
 */
class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_tayang_bertambah_satu_dan_tamu_boleh_mencatat(): void
    {
        $guide = Guide::factory()->forPlacements('listing_form')->create();

        $this->post(route('guides.view', $guide))->assertNoContent();

        $guide->refresh();
        $this->assertSame(1, $guide->views_count);
        $this->assertSame(0, $guide->interactions_count);
    }

    public function test_interaksi_bertambah_satu(): void
    {
        $guide = Guide::factory()->forPlacements('listing_form')->create();

        $this->post(route('guides.interact', $guide))->assertNoContent();

        $guide->refresh();
        $this->assertSame(0, $guide->views_count);
        $this->assertSame(1, $guide->interactions_count);
    }

    public function test_panduan_draf_dan_arsip_tidak_bisa_dicatat(): void
    {
        $draf = Guide::factory()->draft()->forPlacements('listing_form')->create();
        $arsip = Guide::factory()->archived()->forPlacements('listing_form')->create();

        foreach ([$draf, $arsip] as $guide) {
            $this->post(route('guides.view', $guide))->assertNotFound();
            $this->post(route('guides.interact', $guide))->assertNotFound();
            $this->assertSame(0, $guide->fresh()->views_count);
            $this->assertSame(0, $guide->fresh()->interactions_count);
        }
    }

    public function test_pencatatan_hanya_mengubah_counter_dan_tidak_menyimpan_data_pribadi(): void
    {
        $guide = Guide::factory()->forPlacements('listing_form')->create();
        $sebelum = $guide->fresh()->getAttributes();

        $this->post(route('guides.view', $guide))->assertNoContent();

        $sesudah = $guide->fresh()->getAttributes();
        $berubah = array_keys(array_diff_assoc($sesudah, $sebelum));

        // Tidak ada kolom baru (misalnya user_id atau ip), dan hanya counter yang berubah.
        $this->assertSame(array_keys($sebelum), array_keys($sesudah));
        $this->assertContains('views_count', $berubah);
        $this->assertSame([], array_values(array_diff($berubah, ['views_count', 'updated_at'])));
        $this->assertDatabaseCount('guides', 1);
    }

    public function test_satu_panduan_bisa_tampil_di_banyak_lokasi(): void
    {
        $guide = Guide::factory()->forPlacements('listing_form', 'detail_page')->create();

        $this->assertTrue(Guide::forPlacement('listing_form')->whereKey($guide->id)->exists());
        $this->assertTrue(Guide::forPlacement('detail_page')->whereKey($guide->id)->exists());
        $this->assertFalse(Guide::forPlacement('dashboard')->whereKey($guide->id)->exists());
    }

    public function test_sync_lokasi_menghapus_yang_tidak_dipilih_dan_mengabaikan_nilai_tidak_valid(): void
    {
        $guide = Guide::factory()->forPlacements('listing_form', 'dashboard')->create();

        $guide->syncPlacements(['dashboard', 'tidak_ada']);

        $this->assertSame(['dashboard'], $guide->fresh()->placement_keys);
        $this->assertDatabaseCount('guide_placements', 1);
    }

    public function test_komponen_hanya_menampilkan_panduan_terbit_untuk_lokasinya(): void
    {
        Guide::factory()->forPlacements('listing_form')->create(['title' => 'Tampil di listing']);
        Guide::factory()->draft()->forPlacements('listing_form')->create(['title' => 'Masih draf']);
        Guide::factory()->archived()->forPlacements('listing_form')->create(['title' => 'Sudah diarsipkan']);
        Guide::factory()->forPlacements('dashboard')->create(['title' => 'Khusus dashboard']);

        $this->blade('<x-guide-tip placement="listing_form" />')
            ->assertSee('Tampil di listing')
            ->assertSee('Saya paham')
            ->assertDontSee('Masih draf')
            ->assertDontSee('Sudah diarsipkan')
            ->assertDontSee('Khusus dashboard');
    }

    public function test_komponen_kosong_bila_tidak_ada_panduan_terbit_untuk_lokasinya(): void
    {
        Guide::factory()->forPlacements('dashboard')->create(['title' => 'Khusus dashboard']);

        $this->blade('<x-guide-tip placement="detail_page" />')
            ->assertDontSee('Khusus dashboard')
            ->assertDontSee('Saya paham');
    }
}
