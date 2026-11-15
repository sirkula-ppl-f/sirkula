<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu permintaan pembayaran (payment intent) per pesanan: order_id unik (FR-016).
     * Nominal diambil dari total pesanan di server, bukan dari input pengguna.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('provider_ref', 64)->unique(); // order_id yang dikirim ke Midtrans
            $table->string('snap_token')->nullable();
            $table->text('redirect_url')->nullable();
            $table->string('method', 40)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->string('status', 20)->default('UNKNOWN')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
