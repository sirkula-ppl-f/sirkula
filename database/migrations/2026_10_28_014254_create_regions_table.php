<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('province', 100);
            $table->string('name', 150);
            $table->enum('type', [
                'KABUPATEN',
                'KOTA',
            ]);
            $table->timestamps();

            $table->unique([
                'province',
                'name',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};