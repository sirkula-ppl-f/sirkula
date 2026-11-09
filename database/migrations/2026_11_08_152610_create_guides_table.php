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
            // listing_form | detail_page | dashboard
            $table->string('placement')->default('listing_form')->index();
            // draft | published | archived
            $table->string('status')->default('draft')->index();
            // Hanya counter agregat, tanpa data pribadi
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('interactions_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
