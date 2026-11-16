<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            if (! Schema::hasColumn('listing_images', 'listing_id')) {
                $table->foreignId('listing_id')->after('id')->constrained('listings')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('listing_images', 'path_foto')) {
                $table->string('path_foto')->after('listing_id');
            }
            if (! Schema::hasColumn('listing_images', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('path_foto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropForeign(['listing_id']);
            $table->dropColumn(['listing_id', 'path_foto', 'is_primary']);
        });
    }
};
