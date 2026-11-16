<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            // draft | published | archived
            $table->string('status')->default('draft')->index();
            // Hanya counter agregat, tanpa data pribadi
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('interactions_count')->default(0);
            $table->timestamps();
        });

        // listing_form | detail_page | dashboard
        Schema::create('guide_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->string('placement')->index();
            $table->unique(['guide_id', 'placement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_placements');
        Schema::dropIfExists('guides');
    }
};
