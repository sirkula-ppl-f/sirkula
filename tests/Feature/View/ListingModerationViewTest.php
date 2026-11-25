<?php

namespace Tests\Feature\View;

use App\Enums\ListingModerationStatus;
use App\Support\ListingStatusLabel;
use Tests\TestCase;

/**
 * Komponen tampilan moderasi listing (PBI-06) yang boleh dipakai modul lain:
 * <x-listing-moderation.status-badge> dan App\Support\ListingStatusLabel.
 */
class ListingModerationViewTest extends TestCase
{
    public function test_status_badge_menampilkan_teks_untuk_setiap_status(): void
    {
        $harapan = [
            [ListingModerationStatus::Visible, 'Diizinkan tampil', 'bg-brand-50'],
            [ListingModerationStatus::Hidden, 'Dinonaktifkan admin', 'bg-red-50'],
            [null, '—', 'bg-gray-100'],
        ];

        foreach ($harapan as [$status, $label, $warna]) {
            $view = $this->blade(
                '<x-listing-moderation.status-badge :status="$status" class="ms-1" dusk="badge-uji" />',
                ['status' => $status],
            );

            $view->assertSeeText($label)
                ->assertSee($warna, false)
                ->assertSee('whitespace-nowrap', false)
                ->assertSee('ms-1', false)
                ->assertSee('dusk="badge-uji"', false);

            foreach (array_diff(['Diizinkan tampil', 'Dinonaktifkan admin', '—'], [$label]) as $lain) {
                $view->assertDontSeeText($lain);
            }
        }
    }

    public function test_label_status_listing_untuk_nilai_dikenal_tidak_dikenal_dan_kosong(): void
    {
        $this->assertSame('Draf', ListingStatusLabel::review('DRAFT'));
        $this->assertSame('Menunggu verifikasi hub', ListingStatusLabel::review('PENDING_REVIEW'));
        $this->assertSame('Disetujui hub', ListingStatusLabel::review('APPROVED'));
        $this->assertSame('Ditolak hub', ListingStatusLabel::review('REJECTED'));
        $this->assertSame('Dipublikasikan', ListingStatusLabel::publish('ACTIVE'));
        $this->assertSame('Tidak dipublikasikan', ListingStatusLabel::publish('INACTIVE'));
        $this->assertSame('per barang', ListingStatusLabel::saleUnit('ITEM'));
        $this->assertSame('per lot', ListingStatusLabel::saleUnit('LOT'));

        // Nilai yang belum punya label ditampilkan apa adanya; kosong atau null menjadi tanda pisah.
        $this->assertSame('PENDING', ListingStatusLabel::review('PENDING'));
        $this->assertSame('ARCHIVED', ListingStatusLabel::publish('ARCHIVED'));
        $this->assertSame('KG', ListingStatusLabel::saleUnit('KG'));

        foreach ([null, ''] as $kosong) {
            $this->assertSame('—', ListingStatusLabel::review($kosong));
            $this->assertSame('—', ListingStatusLabel::publish($kosong));
            $this->assertSame('—', ListingStatusLabel::saleUnit($kosong));
        }
    }
}
