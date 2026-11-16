<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PBI-05 (FR-008): harga acuan material per provinsi, dalam rupiah per kg.
 *
 * Append-only: tidak ada updated_at. Koreksi dilakukan dengan menerbitkan entri baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->constrained('regions')->restrictOnDelete(); // provinsi (parent_id null)
            $table->unsignedInteger('price_per_kg'); // rupiah bulat
            $table->date('effective_date');
            $table->string('source', 255);
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['material_id', 'region_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_prices');
    }
};
