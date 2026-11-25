<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PBI-06: tata letak form filter di layar desktop.
 *
 * Sidebar admin memakan 256px, jadi pada lebar 1024–1279px konten hanya ±700px. Form filter tetap
 * dua kolom sampai breakpoint xl supaya teks pilihan (misalnya "Semua status verifikasi" dan
 * "Dinonaktifkan") tidak terpotong. Pengukuran lebar sungguhan ada di tes Dusk
 * ListingModerationTest::test_filter_tidak_terpotong_di_layar_desktop.
 */
class ListingModerationFilterLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Atribut class milik form dengan atribut dusk $dusk.
     */
    private function kelasForm(string $html, string $dusk): string
    {
        $this->assertSame(
            1,
            preg_match('/<form\b(?=[^>]*\bdusk="'.preg_quote($dusk, '/').'")[^>]*\bclass="([^"]*)"/s', $html, $cocok),
            "Form {$dusk} tidak ditemukan."
        );

        return $cocok[1];
    }

    public function test_form_filter_tetap_dua_kolom_sampai_layar_xl(): void
    {
        $admin = User::factory()->admin()->create();

        $daftar = $this->kelasForm(
            $this->actingAs($admin)->get(route('admin.listings.index'))->assertOk()->getContent(),
            'listing-filter-form'
        );
        $riwayat = $this->kelasForm(
            $this->actingAs($admin)->get(route('admin.listings.moderations'))->assertOk()->getContent(),
            'moderation-filter-form'
        );

        foreach (['listing-filter-form' => $daftar, 'moderation-filter-form' => $riwayat] as $form => $kelas) {
            $this->assertStringContainsString('sm:grid-cols-2', $kelas, $form);
            $this->assertStringContainsString('sm:items-end', $kelas, $form);
            $this->assertDoesNotMatchRegularExpression('/(^|\s)lg:grid-cols-/', $kelas, "{$form}: banyak kolom di lg membuat filter terpotong.");
        }

        $this->assertStringContainsString('xl:grid-cols-4', $daftar);
        // Kolom Aksi diberi lebar tetap; kolom tanggal dan tombol mengikuti lebar isinya.
        $this->assertStringContainsString('xl:grid-cols-[12rem_1fr_auto_auto_auto]', $riwayat);
    }
}
