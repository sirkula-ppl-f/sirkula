<?php

namespace App\Services;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Exceptions\ListingModerationException;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
