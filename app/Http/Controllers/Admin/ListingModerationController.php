<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingModerationAction;
use App\Enums\ListingModerationStatus;
use App\Enums\ListingType;
use App\Exceptions\ListingModerationException;
use App\Http\Controllers\Admin\Concerns\ListsMasterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateListingRequest;
use App\Models\Listing;
use App\Services\ListingModerationService;
use App\Support\RequestId;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin mencari listing, menonaktifkan atau memulihkannya dengan alasan, dan melihat riwayat moderasi
 * (PBI-06, FR-020/FR-021).
 *
 * Riwayat moderasi append-only: sengaja tidak ada aksi ubah atau hapus riwayat.
 */
class ListingModerationController extends Controller
{
    use ListsMasterData;

    /**
     * Panjang maksimal kata kunci pencarian.
     */
    private const SEARCH_MAX = 100;

    public function __construct(private ListingModerationService $moderation) {}

    public function index(Request $request): View|RedirectResponse
    {
        $review = $this->queryText($request, 'verifikasi');

        $filters = [
            'cari' => $this->querySearch($request),
            'tipe' => ListingType::tryFrom((string) $this->queryText($request, 'tipe')),
            'kategori' => $this->queryId($request, 'kategori'),
            'hub' => $this->queryId($request, 'hub'),
            'provinsi' => $this->queryId($request, 'provinsi'),
            'verifikasi' => in_array($review, ListingModerationService::REVIEW_FILTER_VALUES, true) ? $review : null,
            'moderasi' => ListingModerationStatus::tryFrom((string) $this->queryText($request, 'moderasi')),
        ];

        $listings = $this->moderation->paginateForAdmin([
            'search' => $filters['cari'],
            'type' => $filters['tipe'],
            'category' => $filters['kategori'],
            'hub' => $filters['hub'],
            'province' => $filters['provinsi'],
            'review' => $filters['verifikasi'],
            'moderation' => $filters['moderasi'],
        ]);

        return $this->redirectIfPageEmpty($request, $listings) ?? view('admin.listings.index', [
            'listings' => $listings,
            'filters' => $filters,
            'options' => $this->moderation->filterOptions(),
        ]);
    }

    /**
     * Listing DRAFT dan id yang tidak ada merespons 404.
     */
    public function show(Listing $listing): View
    {
        $listing = $this->moderation->loadForAdmin($listing);

        return view('admin.listings.show', [
            'listing' => $listing,
            'blockers' => $this->moderation->visibilityBlockers($listing),
            'orderCount' => $this->moderation->orderCount($listing),
            'history' => $this->moderation->moderationHistory($listing),
        ]);
    }

    public function deactivate(ModerateListingRequest $request, Listing $listing): RedirectResponse
    {
        return $this->moderate($request, $listing, ListingModerationAction::Deactivate);
    }

    public function restore(ModerateListingRequest $request, Listing $listing): RedirectResponse
    {
        return $this->moderate($request, $listing, ListingModerationAction::Restore);
    }

    public function moderations(Request $request): View|RedirectResponse
    {
        $filters = [
            'aksi' => ListingModerationAction::tryFrom((string) $this->queryText($request, 'aksi')),
            'cari' => $this->querySearch($request),
            'dari' => $this->queryDate($request, 'dari'),
            'sampai' => $this->queryDate($request, 'sampai'),
        ];

        $moderations = $this->moderation->paginateModerations([
            'action' => $filters['aksi'],
            'search' => $filters['cari'],
            'from' => $filters['dari'],
            'until' => $filters['sampai'],
        ]);

        return $this->redirectIfPageEmpty($request, $moderations) ?? view('admin.listings.moderations', [
            'moderations' => $moderations,
            'filters' => $filters,
        ]);
    }

    /**
     * Penolakan aturan bisnis (sudah dicatat service sebagai audit REJECTED) ditampilkan sebagai flash error.
     */
    private function moderate(ModerateListingRequest $request, Listing $listing, ListingModerationAction $action): RedirectResponse
    {
        $reason = (string) $request->validated('reason');
        $requestId = RequestId::for($request);

        try {
            match ($action) {
                ListingModerationAction::Deactivate => $this->moderation->deactivate($listing, $request->user(), $reason, $requestId),
                ListingModerationAction::Restore => $this->moderation->restore($listing, $request->user(), $reason, $requestId),
            };
        } catch (ListingModerationException $e) {
            // Listing draf tidak punya halaman detail, jadi kembali ke daftar.
            $target = $listing->review_status === Listing::REVIEW_DRAFT
                ? route('admin.listings.index')
                : route('admin.listings.show', $listing);

            return redirect($target)->with('error', $e->getMessage());
        }

        $key = $action === ListingModerationAction::Deactivate ? 'deactivated' : 'restored';
        $redirect = redirect()->route('admin.listings.show', $listing)
            ->with('status', __("listing-moderation.flash.{$key}", ['title' => (string) $listing->title]));

        if ($action === ListingModerationAction::Restore) {
            $blockers = $this->moderation->visibilityBlockers($listing->load(['seller', 'hub']));

            if ($blockers !== []) {
                $redirect->with('visibility_note', __('listing-moderation.flash.restored_not_visible', [
                    'reasons' => collect($blockers)->map(fn (string $key) => __($key))->implode(' '),
                ]));
            }
        }

        return $redirect;
    }

    /**
     * Kata kunci dari query string, dirapikan dan dibatasi panjangnya. Kosong dianggap tidak diisi.
     */
    private function querySearch(Request $request): ?string
    {
        $value = trim((string) $this->queryText($request, 'cari'));

        return $value === '' ? null : mb_substr($value, 0, self::SEARCH_MAX);
    }

    /**
     * Id dari query string. Selain bilangan bulat positif dianggap tidak diisi.
     */
    private function queryId(Request $request, string $key): ?int
    {
        $value = $this->queryText($request, $key);

        return $value !== null && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Tanggal 'Y-m-d' dari query string. Tanggal yang tidak valid dianggap tidak diisi.
     */
    private function queryDate(Request $request, string $key): ?string
    {
        $value = $this->queryText($request, $key);

        if ($value === null) {
            return null;
        }

        // Tanggal yang meluap (misalnya 2026-13-45) ikut ditolak karena hasil format-nya berbeda.
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
