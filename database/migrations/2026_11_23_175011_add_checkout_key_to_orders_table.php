<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'checkout_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('checkout_key', 100)->nullable();
            });
        }

        if (! Schema::hasIndex('orders', 'orders_checkout_key_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('checkout_key', 'orders_checkout_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('orders', 'orders_checkout_key_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_checkout_key_unique');
            });
        }

        // Kolom checkout_key sengaja dipertahankan karena dapat digunakan
        // oleh kompatibilitas data pesanan dan pengembangan PBI-20.
    }
};
