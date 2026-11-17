<?php

namespace Tests\Browser;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Alur panduan dan edukasi (PBI-18) di browser sungguhan.
 *
 * Setelah klik yang memuat halaman baru, tes selalu menunggu (waitFor*) supaya tidak
 * memeriksa halaman lama. Tes keempat membutuhkan <x-guide-tip placement="dashboard" />
 * sudah terpasang di halaman dashboard warga.
 */
class GuideBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function tungguAngka(Guide $guide, string $kolom, int $nilai): void
    {
        for ($i = 0; $i < 20; $i++) {
            if ($guide->fresh()->{$kolom} === $nilai) {
                $this->assertSame($nilai, $guide->fresh()->{$kolom});

                return;
            }

            usleep(250_000);
        }

        $this->assertSame($nilai, $guide->fresh()->{$kolom});
    }

    public function test_admin_membuat_panduan_dengan_dua_lokasi(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.guides.index'))
                ->waitForText('Panduan dan Edukasi')
                ->click('@create-guide-button')
                ->waitForLocation('/admin/panduan/buat')
                ->waitFor('@title-input')
                ->type('@title-input', 'Satuan lot untuk material')
                ->type('@body-input', 'Cantumkan berat total per lot agar pembeli bisa membandingkan.')
                ->check('@placement-listing_form')
                ->check('@placement-detail_page')
                ->select('@status-input', 'published')
                ->click('@save-guide-button')
                ->waitForLocation('/admin/panduan')
                ->waitForText('Satuan lot untuk material')
                ->assertSee('Panduan dibuat.')
                ->assertSee('Form listing, Halaman detail');
        });

        $this->assertDatabaseHas('guides', ['title' => 'Satuan lot untuk material', 'views_count' => 0]);
        $this->assertDatabaseCount('guide_placements', 2);
    }

    public function test_form_tanpa_lokasi_menampilkan_pesan_kesalahan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.guides.create'))
                ->waitFor('@title-input')
                ->type('@title-input', 'Panduan tanpa lokasi')
                ->type('@body-input', 'Isi cuplikan panduan.')
                ->click('@save-guide-button')
                ->waitForText('Pilih minimal satu lokasi tampil.')
                ->assertPathIs('/admin/panduan/buat')
                ->assertInputValue('@title-input', 'Panduan tanpa lokasi');
        });

        $this->assertDatabaseCount('guides', 0);
    }

    public function test_admin_mengubah_lokasi_panduan(): void
    {
        $admin = User::factory()->admin()->create();
        Guide::factory()->forPlacements('listing_form', 'detail_page')->create([
            'title' => 'Satuan lot untuk material',
            'slug' => 'satuan-lot',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.guides.index'))
                ->waitFor('@guide-row-satuan-lot')
                ->assertSeeIn('@guide-row-satuan-lot', 'Halaman detail')
                ->click('@edit-guide-satuan-lot')
                ->waitFor('@placement-detail_page')
                ->uncheck('@placement-detail_page')
                ->click('@save-guide-button')
                ->waitForLocation('/admin/panduan')
                ->waitFor('@guide-row-satuan-lot')
                ->assertSee('Panduan diperbarui.')
                ->assertSeeIn('@guide-row-satuan-lot', 'Form listing')
                ->assertDontSeeIn('@guide-row-satuan-lot', 'Halaman detail');
        });

        $this->assertDatabaseCount('guide_placements', 1);
    }

    public function test_warga_melihat_cuplikan_di_dashboard_dan_tayang_serta_interaksi_tercatat(): void
    {
        $warga = User::factory()->create();
        $guide = Guide::factory()->forPlacements('dashboard')->create([
            'title' => 'Satuan lot untuk material',
        ]);
        Guide::factory()->draft()->forPlacements('dashboard')->create(['title' => 'Panduan masih draf']);

        $this->browse(function (Browser $browser) use ($warga, $guide) {
            $browser->loginAs($warga)
                ->visit(route('dashboard'))
                ->waitFor('@guide-tip-dashboard')
                ->scrollIntoView('@guide-tip-dashboard')
                ->assertSeeIn('@guide-tip-dashboard', 'Satuan lot untuk material')
                ->assertDontSee('Panduan masih draf');

            // Tayang dicatat sekali saat cuplikan terlihat di layar.
            $this->tungguAngka($guide, 'views_count', 1);
            $this->assertSame(0, $guide->fresh()->interactions_count);

            $browser->click('[data-guide-tip-ack]')
                ->waitForText('Terima kasih!');

            $this->tungguAngka($guide, 'interactions_count', 1);
        });
    }
}
