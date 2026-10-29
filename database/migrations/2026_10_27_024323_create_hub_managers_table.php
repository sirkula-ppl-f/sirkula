<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_managers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hub_id')
                ->constrained('hubs')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('assignment_status', [
                'ACTIVE',
                'INACTIVE',
            ])->default('ACTIVE');

            $table->timestamp('assigned_at')->useCurrent();

            $table->timestamp('deactivated_at')->nullable();

            $table->timestamps();

            $table->unique(['hub_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_managers');
    }
};