<?php

namespace App\Services;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Enums\ListingType;
use App\Exceptions\ListingModerationException;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Hub;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\OrderItem;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moderasi listing oleh admin (PBI-06, FR-020) beserta jejak auditnya (FR-021).
 *
 * - Satu-satunya yang boleh mengubah listings.moderation_status dan menulis listing_moderations.
 * - Hanya kolom moderation_status yang diubah. review_status, publish_status, stok, harga, hub, dan
 *   kolom lain tidak pernah disentuh, jadi pemulihan tidak melewati verifikasi hub.
 * - Tidak menulis ke orders, order_items, payments, payment_events, atau cart_items.
 */
class ListingModerationService
{
    public const AUDIT_SUCCESS = 'SUCCESS';

    public const AUDIT_REJECTED = 'REJECTED';

    /**
     * Panjang maksimal listing_moderations.request_id.
     */
    public const REQUEST_ID_MAX = 64;

    public const PER_PAGE = 15;

    /**
     * Pilihan filter status verifikasi di halaman admin. DRAFT tidak dimoderasi, jadi tidak ditawarkan.
     * Nilai lain di database (misalnya PENDING dari factory) tetap tampil apa adanya, hanya tidak bisa difilter.
     */
    public const REVIEW_FILTER_VALUES = [Listing::REVIEW_PENDING, Listing::REVIEW_APPROVED, 'REJECTED'];

    /**
     * Nonaktifkan visibilitas listing. Alasan sudah divalidasi FormRequest.
     *
     * @throws ListingModerationException listing draf atau sudah dinonaktifkan (dicatat sebagai audit REJECTED)
     * @throws AuthorizationException bila $admin bukan admin
     */
    public function deactivate(Listing $listing, User $admin, string $reason, string $requestId): ListingModeration
    {
        return $this->moderate($listing, $admin, ListingModerationAction::Deactivate, $reason, $requestId);
    }

    /**
     * Pulihkan visibilitas listing: hanya moderation_status kembali VISIBLE. Listing baru tampil di
     * katalog bila syarat lain terpenuhi; lihat visibilityBlockers().
     *
     * @throws ListingModerationException listing draf atau tidak sedang dinonaktifkan (dicatat sebagai audit REJECTED)
     * @throws AuthorizationException bila $admin bukan admin
     */
    public function restore(Listing $listing, User $admin, string $reason, string $requestId): ListingModeration
    {
        return $this->moderate($listing, $admin, ListingModerationAction::Restore, $reason, $requestId);
    }

    /**
     * Key lang alasan listing tidak tampil di katalog, urut sama dengan syarat Listing::scopePurchasable().
     * Kosong jika dan hanya jika $listing->isPurchasable() bernilai true.
     *
     * Relasi seller dan hub sebaiknya sudah dimuat supaya tidak ada query per listing.
     *
     * @return list<string>
     */
    public function visibilityBlockers(Listing $listing): array
    {
        $blockers = [];

        if ($listing->moderation_status !== ListingModerationStatus::Visible) {
            $blockers[] = 'listing-moderation.blockers.moderated';
        }

        if ($listing->review_status !== Listing::REVIEW_APPROVED) {
            $blockers[] = $listing->review_status === Listing::REVIEW_PENDING
                ? 'listing-moderation.blockers.review_pending'
                : 'listing-moderation.blockers.review_not_approved';
        }

        if ($listing->publish_status !== Listing::PUBLISH_ACTIVE) {
            $blockers[] = 'listing-moderation.blockers.not_published';
        }

        if (! ($listing->stock > 0)) {
            $blockers[] = 'listing-moderation.blockers.out_of_stock';
        }

        if ($listing->seller?->isActive() !== true) {
            $blockers[] = 'listing-moderation.blockers.seller_inactive';
        }

        if ($listing->hub_id !== null && $listing->hub?->status !== 'ACTIVE') {
            $blockers[] = 'listing-moderation.blockers.hub_inactive';
        }

        return $blockers;
    }

    /**
     * Daftar listing untuk halaman admin: semua listing kecuali DRAFT, terbaru di atas. Relasi yang dipakai
     * tabel (termasuk isPurchasable()) dimuat sekaligus, jadi jumlah query tidak bergantung pada jumlah baris.
     *
     * @param  array{search?: ?string, type?: ?ListingType, category?: ?int, hub?: ?int, province?: ?int, review?: ?string, moderation?: ?ListingModerationStatus}  $filters
     * @return LengthAwarePaginator<int, Listing>
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = $this->moderatable()->with(['seller', 'hub', 'region', 'category', 'material']);

        if (isset($filters['search']) && $filters['search'] !== '') {
            $term = $filters['search'];

            $query->where(function (Builder $q) use ($term) {
                $this->whereContains($q, 'title', $term);

                // Kata kunci berupa angka juga dicocokkan ke ID listing.
                if (ctype_digit($term)) {
                    $q->orWhere($q->qualifyColumn('id'), (int) $term);
                }
            });
        }

        if (isset($filters['type'])) {
            $query->where($query->qualifyColumn('listing_type'), $filters['type']->value);
        }

        if (isset($filters['category'])) {
            $query->where($query->qualifyColumn('category_id'), $filters['category']);
        }

        if (isset($filters['hub'])) {
            $query->where($query->qualifyColumn('hub_id'), $filters['hub']);
        }

        if (isset($filters['province'])) {
            $province = $filters['province'];

            // Provinsi itu sendiri atau wilayah di bawahnya (kab/kota).
            $query->where(function (Builder $q) use ($province) {
                $q->where($q->qualifyColumn('region_id'), $province)
                    ->orWhereIn($q->qualifyColumn('region_id'), Region::query()->select('id')->where('parent_id', $province));
            });
        }

        if (isset($filters['review'])) {
            $query->where($query->qualifyColumn('review_status'), $filters['review']);
        }

        if (isset($filters['moderation'])) {
            $query->where($query->qualifyColumn('moderation_status'), $filters['moderation']->value);
        }

        return $query->orderByDesc($query->qualifyColumn('id'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Muat relasi untuk halaman detail admin. Listing DRAFT dianggap tidak ada (404).
     *
     * @throws ModelNotFoundException
     */
    public function loadForAdmin(Listing $listing): Listing
    {
        if ($listing->review_status === Listing::REVIEW_DRAFT) {
            throw (new ModelNotFoundException)->setModel(Listing::class, [$listing->getKey()]);
        }

        return $listing->load(['seller', 'hub', 'region', 'category', 'material', 'images']);
    }

    /**
     * Riwayat moderasi satu listing beserta admin pelakunya, terbaru di atas.
     *
     * @return EloquentCollection<int, ListingModeration>
     */
    public function moderationHistory(Listing $listing): EloquentCollection
    {
        return $listing->moderations()->with('admin')->get();
    }

    /**
     * Jumlah pesanan (bukan item) yang memuat listing ini, dihitung dari order_items.listing_id. Informatif saja.
     */
    public function orderCount(Listing $listing): int
    {
        return OrderItem::query()
            ->where('listing_id', $listing->getKey())
            ->distinct()
            ->count('order_id');
    }

    /**
     * Semua riwayat moderasi untuk halaman admin, terbaru di atas.
     *
     * Tanggal filter dibaca sebagai tanggal WIB: dari pukul 00.00 WIB tanggal $from sampai sebelum
     * pukul 00.00 WIB hari setelah $until. created_at tersimpan dalam UTC.
     *
     * @param  array{action?: ?ListingModerationAction, search?: ?string, from?: ?string, until?: ?string}  $filters  tanggal 'Y-m-d'
     * @return LengthAwarePaginator<int, ListingModeration>
     */
    public function paginateModerations(array $filters = []): LengthAwarePaginator
    {
        $query = ListingModeration::query()->with(['listing', 'admin']);

        if (isset($filters['action'])) {
            $query->where($query->qualifyColumn('action'), $filters['action']->value);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $term = $filters['search'];
            $matchingListings = Listing::query()->select('id');
            $this->whereContains($matchingListings, 'title', $term);

            $query->where(function (Builder $q) use ($matchingListings, $term) {
                $q->whereIn($q->qualifyColumn('listing_id'), $matchingListings);

                if (ctype_digit($term)) {
                    $q->orWhere($q->qualifyColumn('listing_id'), (int) $term);
                }
            });
        }

        if (isset($filters['from'])) {
            $query->where($query->qualifyColumn('created_at'), '>=', $this->startOfWibDayInUtc($filters['from']));
        }

        if (isset($filters['until'])) {
            $query->where($query->qualifyColumn('created_at'), '<', $this->startOfWibDayInUtc($filters['until'], 1));
        }

        return $query->orderByDesc($query->qualifyColumn('created_at'))
            ->orderByDesc($query->qualifyColumn('id'))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Pilihan filter halaman daftar listing. Kategori nonaktif dan hub yang diarsipkan diberi tanda.
     *
     * @return array{categories: array<int, string>, hubs: array<int, string>, provinces: array<int, string>}
     */
    public function filterOptions(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'is_active'])
                ->mapWithKeys(fn (Category $category) => [
                    $category->id => $category->is_active
                        ? $category->name
                        : __('listing-moderation.admin.inactive_category_option', ['name' => $category->name]),
                ])
                ->all(),
            'hubs' => Hub::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'status'])
                ->mapWithKeys(fn (Hub $hub) => [
                    $hub->id => $hub->status === 'ACTIVE'
                        ? $hub->name
                        : __('listing-moderation.admin.archived_hub_option', ['name' => $hub->name]),
                ])
                ->all(),
            'provinces' => Region::query()->whereNull('parent_id')->orderBy('name')->orderBy('id')->pluck('name', 'id')->all(),
        ];
    }

    /**
     * Listing yang boleh dimoderasi dan tampil di halaman admin: semua kecuali DRAFT.
     *
     * @return Builder<Listing>
     */
    private function moderatable(): Builder
    {
        $query = Listing::query();

        return $query->where($query->qualifyColumn('review_status'), '!=', Listing::REVIEW_DRAFT);
    }

    /**
     * $column memuat $term. Karakter %, _, dan ! di-escape supaya dicari sebagai huruf biasa.
     *
     * Disalin dari HandlesMasterData::searchName() (PBI-04), yang kolomnya terkunci ke "name".
     * ESCAPE '!' ditulis langsung di SQL karena backslash berperilaku beda di MySQL dan SQLite.
     */
    private function whereContains(Builder $query, string $column, string $term): void
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $wrapped = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));

        $query->whereRaw("{$wrapped} LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
    }

    /**
     * Awal hari $date ('Y-m-d') menurut WIB, ditambah $addDays hari, dalam UTC ('Y-m-d H:i:s').
     * Zona waktu WIB diambil dari ReferencePriceService::TIMEZONE (PBI-05), sama dengan IndonesianFormat.
     */
    private function startOfWibDayInUtc(string $date, int $addDays = 0): string
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $date, ReferencePriceService::TIMEZONE)
            ->addDays($addDays)
            ->utc()
            ->toDateTimeString();
    }

    private function moderate(
        Listing $listing,
        User $admin,
        ListingModerationAction $action,
        string $reason,
        string $requestId,
    ): ListingModeration {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException(__('listing-moderation.errors.forbidden'));
        }

        if ($requestId === '' || strlen($requestId) > self::REQUEST_ID_MAX) {
            throw new InvalidArgumentException('Request ID moderasi harus 1–'.self::REQUEST_ID_MAX.' karakter. Pakai App\Support\RequestId::for().');
        }

        $reason = trim($reason);

        try {
            [$moderation, $attributes] = DB::transaction(function () use ($listing, $admin, $action, $reason, $requestId) {
                // Baris dikunci supaya dua admin (atau dua tab) tidak memproses listing yang sama bersamaan.
                $locked = Listing::query()->lockForUpdate()->findOrFail($listing->getKey());

                $this->ensureApplicable($locked, $action);

                $moderation = ListingModeration::create([
                    'listing_id' => $locked->getKey(),
                    'action' => $action,
                    'reason' => $reason,
                    'admin_id' => $admin->getKey(),
                    'request_id' => $requestId,
                    'review_status_snapshot' => $locked->review_status,
                    'publish_status_snapshot' => $locked->publish_status,
                ]);

                $locked->update(['moderation_status' => $action->targetStatus()]);

                $this->audit($admin, $action, $locked->getKey(), self::AUDIT_SUCCESS, $reason);

                return [$moderation, $locked->getAttributes()];
            });
        } catch (ListingModerationException $e) {
            // Ditulis di luar transaksi di atas supaya tidak ikut di-rollback.
            $this->audit($admin, $action, $listing->getKey(), self::AUDIT_REJECTED, $e->getMessage());

            throw $e;
        }

        // Instance milik pemanggil ikut memuat status terbaru; relasi yang sudah dimuat tetap ada.
        $listing->setRawAttributes($attributes, true);

        return $moderation;
    }

    /**
     * @throws ListingModerationException
     */
    private function ensureApplicable(Listing $listing, ListingModerationAction $action): void
    {
        if ($listing->review_status === Listing::REVIEW_DRAFT) {
            throw ListingModerationException::draft();
        }

        if ($listing->moderation_status === $action->targetStatus()) {
            throw $action === ListingModerationAction::Deactivate
                ? ListingModerationException::alreadyHidden()
                : ListingModerationException::alreadyVisible();
        }
    }

    /**
     * Satu baris audit_logs (FR-021). Kolom request ID belum ada di audit_logs (PBI-19), jadi
     * request ID hanya tersimpan di listing_moderations untuk aksi yang berhasil.
     */
    private function audit(User $admin, ListingModerationAction $action, int $listingId, string $result, string $reason): void
    {
        $request = request();

        AuditLog::create([
            'user_id' => $admin->getKey(),
            'action' => $action->auditAction(),
            'entity_type' => Listing::class,
            'entity_id' => $listingId,
            'result' => $result,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
