<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STUB sementara untuk PBI-20 (catatan aktivitas sirkular).
 * Kolom mengikuti kontrak ERD, Class Diagram (Order & OrderItem), serta Listing/CartItem.
 * Penjaga Schema::hasTable memastikan migrasi ini dilewati bila tabel orders/order_items
 * asli dari PBI-12/13 sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
                $table->string('order_number', 50)->unique();
                $table->string('checkout_key', 100)->nullable()->index();
                $table->string('status', 30)->default('PENDING_PAYMENT')->index();
                $table->unsignedBigInteger('total')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable()->index();
                $table->timestamps();

                $table->index(['buyer_id', 'status', 'paid_at']);
                $table->index(['seller_id', 'status', 'paid_at']);
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->string('title_snapshot');
                $table->string('type_snapshot', 20)->index();
                $table->unsignedBigInteger('unit_price')->default(0);
                $table->decimal('weight_per_unit_snapshot', 10, 2)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedBigInteger('subtotal')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
