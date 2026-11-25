<?php

namespace Tests\Feature\ListingModeration;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Exceptions\ListingModerationImmutableException;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PBI-06: kontrak skema, label enum, dan sifat append-only riwayat moderasi.
 */
class ListingModerationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_tabel_riwayat_sesuai_kontrak_tanpa_updated_at(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'listing_id', 'action', 'reason', 'admin_id', 'request_id', 'review_status_snapshot', 'publish_status_snapshot', 'created_at'],
            Schema::getColumnListing('listing_moderations')
        );
        $this->assertTrue(Schema::hasColumn('listings', 'moderation_status'));
    }

    public function test_listing_baru_berstatus_visible_di_model_dan_database(): void
    {
        $listing = Listing::factory()->create();

        // Tanpa dimuat ulang, model sudah memakai default yang sama dengan kolom database.
        $this->assertSame(ListingModerationStatus::Visible, $listing->moderation_status);
        $this->assertTrue($listing->isPurchasable());
        $this->assertSame('VISIBLE', DB::table('listings')->where('id', $listing->id)->value('moderation_status'));

        // Baris yang disisipkan tanpa model juga memakai default kolom.
        $id = DB::table('listings')->insertGetId(['title' => 'Listing tanpa model']);
        $this->assertSame('VISIBLE', DB::table('listings')->where('id', $id)->value('moderation_status'));
    }

    public function test_label_dan_aturan_enum(): void
    {
        $this->assertSame('Diizinkan tampil', ListingModerationStatus::Visible->label());
        $this->assertSame('Dinonaktifkan admin', ListingModerationStatus::Hidden->label());
        $this->assertSame('Dinonaktifkan', ListingModerationAction::Deactivate->label());
        $this->assertSame('Dipulihkan', ListingModerationAction::Restore->label());

        $this->assertSame(ListingModerationStatus::Hidden, ListingModerationAction::Deactivate->targetStatus());
        $this->assertSame(ListingModerationStatus::Visible, ListingModerationAction::Restore->targetStatus());
        $this->assertSame('LISTING_DEACTIVATED', ListingModerationAction::Deactivate->auditAction());
        $this->assertSame('LISTING_RESTORED', ListingModerationAction::Restore->auditAction());
    }

    public function test_riwayat_tidak_bisa_diubah_lewat_model_dan_data_tetap_utuh(): void
    {
        $moderation = ListingModeration::factory()->create(['reason' => 'Alasan awal yang tercatat.']);
        $sebelum = (array) DB::table('listing_moderations')->where('id', $moderation->id)->first();

        try {
            $moderation->update(['reason' => 'Alasan diubah diam-diam.']);
            $this->fail('Riwayat moderasi tidak boleh bisa diubah.');
        } catch (ListingModerationImmutableException $e) {
            $this->assertSame(__('listing-moderation.immutable.update'), $e->getMessage());
        }

        try {
            $moderation->fresh()->forceFill(['action' => ListingModerationAction::Restore])->save();
            $this->fail('Aksi riwayat moderasi tidak boleh bisa diubah.');
        } catch (ListingModerationImmutableException) {
            // Diharapkan.
        }

        $this->assertSame($sebelum, (array) DB::table('listing_moderations')->where('id', $moderation->id)->first());
    }

    public function test_riwayat_tidak_bisa_dihapus_lewat_model(): void
    {
        $moderation = ListingModeration::factory()->create();

        try {
            $moderation->delete();
            $this->fail('Riwayat moderasi tidak boleh bisa dihapus.');
        } catch (ListingModerationImmutableException $e) {
            $this->assertSame(__('listing-moderation.immutable.delete'), $e->getMessage());
        }

        $this->assertModelExists($moderation);
        $this->assertDatabaseCount('listing_moderations', 1);
    }

    public function test_listing_dan_admin_yang_punya_riwayat_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->create();
        ListingModeration::factory()->forListing($listing)->byAdmin($admin)->create();

        $this->assertDitolakDatabase(fn () => $listing->delete());
        $this->assertDitolakDatabase(fn () => $admin->delete());

        $this->assertModelExists($listing);
        $this->assertModelExists($admin);
    }

    public function test_relasi_moderations_urut_terbaru_di_atas(): void
    {
        $listing = Listing::factory()->create();

        $this->travelTo(now()->subHours(2));
        $pertama = ListingModeration::factory()->forListing($listing)->create();
        $this->travelBack();
        $kedua = ListingModeration::factory()->forListing($listing)->restore()->create();
        $ketiga = ListingModeration::factory()->forListing($listing)->create(); // detik sama: id lebih besar di atas
        ListingModeration::factory()->create(); // listing lain

        $this->assertSame([$ketiga->id, $kedua->id, $pertama->id], $listing->moderations()->pluck('id')->all());
    }

    private function assertDitolakDatabase(callable $hapus): void
    {
        try {
            DB::transaction($hapus);
            $this->fail('FK restrictOnDelete harus menolak penghapusan.');
        } catch (QueryException) {
            // Diharapkan: FK restrict.
        }
    }
}
