<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            // Relasi (sementara tanpa constrained agar tidak error dengan tabel temanmu)
            $table->foreignId('user_id');
            $table->foreignId('hub_id');
            $table->foreignId('category_id');
            $table->foreignId('region_id');

            // Relasi opsional (CUKUP DITULIS SATU KALI)
            $table->foreignId('material_id')->nullable();
            $table->foreignId('storefront_id')->nullable();

            // Detail Listing
            $table->string('title');
            $table->string('listing_type');
            $table->string('condition')->nullable();
            $table->integer('price');
            $table->integer('stock');
            $table->integer('weight_per_lot')->nullable();
            $table->text('description');

            // Status listing
            $table->enum('status', ['DRAFT', 'PENDING_REVIEW', 'APPROVED', 'REJECTED', 'INACTIVE'])->default('DRAFT');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
