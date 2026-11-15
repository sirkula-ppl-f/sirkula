<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STUB sementara untuk PBI-13 (pembayaran). Tabel orders yang asli dibuat PBI-12 (checkout).
 * HAPUS file ini (beserta app/Models/Order.php versi stub dan OrderFactory) saat migrasi
 * orders dari PBI-12 masuk ke develop, lalu sesuaikan nama kolom bila berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('total')->default(0);
            $table->string('status', 20)->default('PENDING_PAYMENT')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
