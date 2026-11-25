<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-06 (FR-020, FR-021): riwayat moderasi listing oleh admin.
 *
 * Append-only: tidak ada updated_at, dan model ListingModeration menolak update/delete.
 * FK restrict: listing dan akun admin yang punya riwayat moderasi tidak bisa dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->restrictOnDelete();
            $table->string('action', 20); // DEACTIVATE | RESTORE
            $table->text('reason');
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('request_id', 64);
            // Status listing saat aksi dilakukan, hanya sebagai konteks.
            $table->string('review_status_snapshot', 20)->nullable();
            $table->string('publish_status_snapshot', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['listing_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_moderations');
    }
};
