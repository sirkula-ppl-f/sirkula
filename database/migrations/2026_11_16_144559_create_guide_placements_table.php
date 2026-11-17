<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // listing_form | detail_page | dashboard
        Schema::create('guide_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->string('placement')->index();
            $table->unique(['guide_id', 'placement']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_placements');
    }
};
