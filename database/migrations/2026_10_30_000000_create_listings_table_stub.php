<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STUB sementara untuk PBI-11 (keranjang). Kolom mengikuti kontrak di Category/Material
 * dan class diagram. HAPUS file ini (beserta app/Models/Listing.php versi stub dan
 * ListingFactory) saat migrasi listings asli dari PBI-07/08 masuk ke develop.
 *
 * Kolom sengaja nullable/berdefault: tes data master (CategoryTest, MaterialTest) menyisipkan
 * baris listings yang hanya berisi category_id atau material_id lewat TabelPemakaianSementara.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listings')) {
            return;
        }

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete(); // penjual
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('listing_type', 20)->nullable();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('price')->default(0); // rupiah per item/lot
            $table->unsignedInteger('stock')->default(0);
            $table->string('sale_unit', 10)->default('ITEM'); // ITEM | LOT
            $table->string('review_status', 20)->default('DRAFT')->index();
            $table->string('publish_status', 20)->default('INACTIVE')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
