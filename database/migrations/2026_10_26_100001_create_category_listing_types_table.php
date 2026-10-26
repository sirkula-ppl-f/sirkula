<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tipe listing (nilai App\Enums\ListingType) yang berlaku untuk tiap kategori.
     */
    public function up(): void
    {
        Schema::create('category_listing_types', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('listing_type', 20);

            $table->unique(['category_id', 'listing_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_listing_types');
    }
};
