<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-06 (FR-020): status moderasi admin, terpisah dari review_status (verifikasi hub) dan
 * publish_status (publikasi penjual). Nilai: VISIBLE | HIDDEN (App\Enums\ListingModerationStatus).
 *
 * Hanya ListingModerationService yang boleh mengubah kolom ini. Listing lama otomatis VISIBLE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('listings', 'moderation_status')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->string('moderation_status', 20)->default('VISIBLE')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('listings', 'moderation_status')) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['moderation_status']);
            $table->dropColumn('moderation_status');
        });
    }
};
