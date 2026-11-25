<?php

namespace Tests\Feature\ListingModeration;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Exceptions\ListingModerationException;
use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\ListingModerationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * PBI-06 / FR-020, FR-021: aturan bisnis ListingModerationService beserta audit.
 */
class ListingModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const ALASAN = 'Foto listing tidak sesuai dengan barang yang dijual.';

    private User $admin;

    private ListingModerationService $moderasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->moderasi = app(ListingModerationService::class);
    }

    /**
     * Isi baris listing langsung dari database, tanpa moderation_status dan updated_at.
     *
     * @return array<string, mixed>
     */
    private function kolomLain(Listing $listing): array
    {
        $baris = (array) DB::table('listings')->where('id', $listing->id)->first();
        unset($baris['moderation_status'], $baris['updated_at']);

        return $baris;
    }

    private function statusDb(Listing $listing): string
    {
        return DB::table('listings')->where('id', $listing->id)->value('moderation_status');
    }

    /**
     * Jalankan aksi yang harus ditolak, lalu kembalikan exception-nya.
     */
    private function ditolak(callable $aksi): ListingModerationException
    {
        try {
            $aksi();
        } catch (ListingModerationException $e) {
            return $e;
        }

        $this->fail('Aksi moderasi seharusnya ditolak.');
    }

    public function test_menonaktifkan_hanya_mengubah_status_moderasi_lalu_mencatat_riwayat_dan_audit(): void
    {
        $listing = Listing::factory()->create(['stock' => 3, 'price' => 75000]);
        $sebelum = $this->kolomLain($listing);

        // ip dan user agent audit diambil dari request yang sedang berjalan.
        $this->app->instance('request', Request::create('/admin/listing/'.$listing->id.'/nonaktifkan', 'PATCH', server: [
            'REMOTE_ADDR' => '10.20.30.40',
            'HTTP_USER_AGENT' => 'Peramban Uji/1.0',
        ]));

        $this->travel(10)->minutes();
        $moderation = $this->moderasi->deactivate($listing, $this->admin, '  '.self::ALASAN.'  ', 'req-nonaktif-0001');

        $this->assertSame('HIDDEN', $this->statusDb($listing));
        $this->assertSame($sebelum, $this->kolomLain($listing), 'Kolom listing selain moderation_status dan updated_at tidak boleh berubah.');
        $this->assertSame(ListingModerationStatus::Hidden, $listing->moderation_status);

        $baris = ListingModeration::sole();
        $this->assertTrue($moderation->is($baris));
        $this->assertSame($listing->id, $baris->listing_id);
        $this->assertSame(ListingModerationAction::Deactivate, $baris->action);
        $this->assertSame(self::ALASAN, $baris->reason);
        $this->assertSame($this->admin->id, $baris->admin_id);
        $this->assertSame('req-nonaktif-0001', $baris->request_id);
        $this->assertSame('APPROVED', $baris->review_status_snapshot);
        $this->assertSame('ACTIVE', $baris->publish_status_snapshot);
        $this->assertNotNull($baris->created_at);

        $audit = AuditLog::sole();
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertSame('LISTING_DEACTIVATED', $audit->action);
        $this->assertSame(Listing::class, $audit->entity_type);
        $this->assertSame($listing->id, (int) $audit->entity_id);
        $this->assertSame('SUCCESS', $audit->result);
        $this->assertSame(self::ALASAN, $audit->reason);
        $this->assertSame('10.20.30.40', $audit->ip_address);
        $this->assertSame('Peramban Uji/1.0', $audit->user_agent);
        $this->assertNotNull($audit->created_at);
    }

    public function test_memulihkan_listing_yang_disetujui_membuatnya_tayang_lagi(): void
    {
        $listing = Listing::factory()->create();
        $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, 'req-nonaktif-0002');
        $sebelum = $this->kolomLain($listing);

        $moderation = $this->moderasi->restore($listing, $this->admin, 'Penjual sudah memperbaiki foto listing.', 'req-pulihkan-0002');

        $this->assertSame('VISIBLE', $this->statusDb($listing));
        $this->assertSame($sebelum, $this->kolomLain($listing));
        $this->assertTrue($listing->fresh()->isPurchasable());
        $this->assertTrue(Listing::purchasable()->whereKey($listing->id)->exists());
        $this->get(route('catalog.show', $listing->id))->assertOk();

        $this->assertSame(ListingModerationAction::Restore, $moderation->action);
        $this->assertSame([$moderation->id], [$listing->moderations()->value('id')], 'Riwayat terbaru di atas.');
        $this->assertDatabaseCount('listing_moderations', 2);
        $this->assertSame(['LISTING_DEACTIVATED', 'LISTING_RESTORED'], AuditLog::query()->orderBy('id')->pluck('action')->all());
        $this->assertSame(['SUCCESS', 'SUCCESS'], AuditLog::query()->orderBy('id')->pluck('result')->all());
    }

    public function test_memulihkan_listing_yang_revisinya_belum_disetujui_tidak_melewati_verifikasi_hub(): void
    {
        $listing = Listing::factory()->create();
        $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, 'req-nonaktif-0003');

        // Fixture alur revisi (PBI-03/07 belum ada): penjual merevisi, listing kembali menunggu verifikasi hub.
        DB::table('listings')->where('id', $listing->id)->update(['review_status' => Listing::REVIEW_PENDING]);
        $sebelum = $this->kolomLain($listing);

        $moderation = $this->moderasi->restore($listing, $this->admin, 'Penjual sudah merevisi deskripsi listing.', 'req-pulihkan-0003');

        $segar = Listing::query()->with(['seller', 'hub'])->findOrFail($listing->id);
        $this->assertSame(ListingModerationStatus::Visible, $segar->moderation_status);
        $this->assertSame(Listing::REVIEW_PENDING, $segar->review_status, 'Pemulihan tidak boleh mengubah status verifikasi hub.');
        $this->assertSame(Listing::PUBLISH_ACTIVE, $segar->publish_status);
        $this->assertSame($sebelum, $this->kolomLain($listing));

        $this->assertFalse($segar->isPurchasable());
        $this->assertFalse(Listing::purchasable()->whereKey($listing->id)->exists());
        $this->get(route('catalog.show', $listing->id))->assertNotFound();
        $this->assertSame(['listing-moderation.blockers.review_pending'], $this->moderasi->visibilityBlockers($segar));
        $this->assertSame(Listing::REVIEW_PENDING, $moderation->review_status_snapshot);
    }

    public function test_menonaktifkan_listing_yang_sudah_dinonaktifkan_ditolak_dan_dicatat_rejected(): void
    {
        $listing = Listing::factory()->create();
        // Tab lain yang masih memegang data lama (status VISIBLE).
        $tabLama = Listing::query()->findOrFail($listing->id);

        $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, 'req-nonaktif-0004');
        $e = $this->ditolak(fn () => $this->moderasi->deactivate($tabLama, $this->admin, 'Alasan dari tab lama yang belum dimuat ulang.', 'req-nonaktif-0005'));

        $this->assertSame(__('listing-moderation.errors.already_hidden'), $e->getMessage());
        $this->assertSame('HIDDEN', $this->statusDb($listing));
        $this->assertDatabaseCount('listing_moderations', 1);

        $ditolak = AuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame(2, AuditLog::count());
        $this->assertSame('LISTING_DEACTIVATED', $ditolak->action);
        $this->assertSame('REJECTED', $ditolak->result);
        $this->assertSame(__('listing-moderation.errors.already_hidden'), $ditolak->reason);
        $this->assertSame(Listing::class, $ditolak->entity_type);
        $this->assertSame($listing->id, (int) $ditolak->entity_id);
        $this->assertSame($this->admin->id, $ditolak->user_id);
    }

    public function test_memulihkan_listing_yang_tidak_dinonaktifkan_ditolak_dan_dicatat_rejected(): void
    {
        $listing = Listing::factory()->create();

        $e = $this->ditolak(fn () => $this->moderasi->restore($listing, $this->admin, 'Memulihkan listing yang masih tampil.', 'req-pulihkan-0006'));

        $this->assertSame(__('listing-moderation.errors.already_visible'), $e->getMessage());
        $this->assertSame('VISIBLE', $this->statusDb($listing));
        $this->assertDatabaseCount('listing_moderations', 0);

        $audit = AuditLog::sole();
        $this->assertSame('LISTING_RESTORED', $audit->action);
        $this->assertSame('REJECTED', $audit->result);
        $this->assertSame(__('listing-moderation.errors.already_visible'), $audit->reason);
    }

    public function test_listing_draf_tidak_bisa_dimoderasi(): void
    {
        $draf = Listing::factory()->create(['review_status' => Listing::REVIEW_DRAFT, 'publish_status' => 'INACTIVE']);
        $drafTersembunyi = Listing::factory()->dinonaktifkanAdmin()->create(['review_status' => Listing::REVIEW_DRAFT]);

        $nonaktif = $this->ditolak(fn () => $this->moderasi->deactivate($draf, $this->admin, self::ALASAN, 'req-draf-0001'));
        $pulihkan = $this->ditolak(fn () => $this->moderasi->restore($drafTersembunyi, $this->admin, self::ALASAN, 'req-draf-0002'));

        $this->assertSame(__('listing-moderation.errors.draft'), $nonaktif->getMessage());
        $this->assertSame(__('listing-moderation.errors.draft'), $pulihkan->getMessage());
        $this->assertSame('VISIBLE', $this->statusDb($draf));
        $this->assertSame('HIDDEN', $this->statusDb($drafTersembunyi));
        $this->assertDatabaseCount('listing_moderations', 0);
        $this->assertSame(['REJECTED', 'REJECTED'], AuditLog::query()->orderBy('id')->pluck('result')->all());
    }

    public function test_hanya_admin_yang_bisa_memoderasi(): void
    {
        $listing = Listing::factory()->create();
        $warga = User::factory()->create();

        try {
            $this->moderasi->deactivate($listing, $warga, self::ALASAN, 'req-warga-0001');
            $this->fail('Warga tidak boleh memoderasi listing.');
        } catch (AuthorizationException $e) {
            $this->assertSame(__('listing-moderation.errors.forbidden'), $e->getMessage());
        }

        $this->assertSame('VISIBLE', $this->statusDb($listing));
        $this->assertDatabaseCount('listing_moderations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_request_id_kosong_atau_lebih_dari_64_karakter_ditolak(): void
    {
        $listing = Listing::factory()->create();

        foreach (['', str_repeat('a', 65)] as $requestId) {
            try {
                $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, $requestId);
                $this->fail('Request ID tidak valid harus ditolak.');
            } catch (InvalidArgumentException) {
                // Diharapkan.
            }
        }

        $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, str_repeat('a', 64));

        $this->assertSame(str_repeat('a', 64), ListingModeration::sole()->request_id);
    }

    public function test_pesanan_lama_pembayaran_dan_keranjang_tetap_utuh_setelah_dinonaktifkan_dan_dipulihkan(): void
    {
        $listing = Listing::factory()->stok(4)->create(['price' => 50000]);
        $pembeli = User::factory()->create();

        $lunas = Order::factory()->paid()->create(['buyer_id' => $pembeli->id, 'seller_id' => $listing->user_id, 'total' => 50000]);
        $menunggu = Order::factory()->create(['buyer_id' => $pembeli->id, 'seller_id' => $listing->user_id, 'total' => 100000]);

        foreach ([[$lunas, 1], [$menunggu, 2]] as [$order, $jumlah]) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'listing_id' => $listing->id,
                'title_snapshot' => $listing->title,
                'unit_price' => 50000,
                'quantity' => $jumlah,
                'subtotal' => 50000 * $jumlah,
            ]);
        }

        Payment::create([
            'order_id' => $menunggu->id,
            'provider_ref' => $menunggu->order_number,
            'gross_amount' => $menunggu->total,
            'status' => 'PENDING',
        ]);
        CartItem::create(['user_id' => $pembeli->id, 'listing_id' => $listing->id, 'quantity' => 1, 'price_seen' => 50000]);

        $potret = fn () => collect(['orders', 'order_items', 'payments', 'payment_events', 'cart_items'])
            ->mapWithKeys(fn (string $tabel) => [$tabel => DB::table($tabel)->orderBy('id')->get()->map(fn ($baris) => (array) $baris)->all()])
            ->all();
        $sebelum = $potret();

        $this->moderasi->deactivate($listing, $this->admin, self::ALASAN, 'req-pesanan-0001');
        $this->assertSame($sebelum, $potret(), 'Penonaktifan tidak boleh mengubah pesanan, pembayaran, atau keranjang.');

        $this->moderasi->restore($listing, $this->admin, 'Penjual sudah memperbaiki foto listing.', 'req-pesanan-0002');
        $this->assertSame($sebelum, $potret(), 'Pemulihan tidak boleh mengubah pesanan, pembayaran, atau keranjang.');

        $this->assertSame(2, OrderItem::query()->where('listing_id', $listing->id)->count());
    }
}
