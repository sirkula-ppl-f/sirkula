<?php

namespace Database\Factories;

use App\Enums\ListingModerationAction;
use App\Models\Listing;
use App\Models\ListingModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Baris riwayat moderasi untuk fixture tes. Factory ini TIDAK mengubah status listing;
 * untuk moderasi sungguhan pakai ListingModerationService.
 *
 * Default: penonaktifan listing baru oleh admin baru.
 *
 * @extends Factory<ListingModeration>
 */
class ListingModerationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'action' => ListingModerationAction::Deactivate,
            'reason' => 'Foto dan deskripsi listing tidak sesuai dengan barang yang dijual.',
            'admin_id' => User::factory()->admin(),
            'request_id' => (string) Str::uuid(),
            'review_status_snapshot' => Listing::REVIEW_APPROVED,
            'publish_status_snapshot' => Listing::PUBLISH_ACTIVE,
        ];
    }

    public function restore(): static
    {
        return $this->state([
            'action' => ListingModerationAction::Restore,
            'reason' => 'Penjual sudah memperbaiki foto dan deskripsi listing.',
        ]);
    }

    /**
     * Riwayat untuk listing ini, dengan snapshot status listing saat ini.
     */
    public function forListing(Listing $listing): static
    {
        return $this->state([
            'listing_id' => $listing->id,
            'review_status_snapshot' => $listing->review_status,
            'publish_status_snapshot' => $listing->publish_status,
        ]);
    }

    public function byAdmin(User $admin): static
    {
        return $this->state(['admin_id' => $admin->id]);
    }
}
