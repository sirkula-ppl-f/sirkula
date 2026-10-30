<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubs', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            $table->enum('type', [
                'BANK_SAMPAH',
                'RW',
                'KOMUNITAS',
            ]);

            $table->foreignId('region_id')
                ->constrained('regions')
                ->restrictOnDelete();

            $table->text('public_address');

            $table->text('description')->nullable();

            $table->enum('status', [
                'ACTIVE',
                'ARCHIVED',
            ])->default('ACTIVE');

            $table->timestamps();

            $table->index(['region_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubs');
    }
};
