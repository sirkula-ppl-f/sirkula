<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inbox notifikasi Midtrans: disimpan dulu, diproses kemudian oleh worker (NFR-006).
     * fingerprint unik mencegah notifikasi ganda diproses dua kali (NFR-003).
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->string('provider_ref', 64)->index();
            $table->string('transaction_status', 30);
            $table->json('payload');
            $table->string('process_status', 20)->default('PENDING')->index();
            $table->string('process_note')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
