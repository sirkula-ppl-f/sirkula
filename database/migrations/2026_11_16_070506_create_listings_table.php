<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    /**

     * Run the migrations.

     */

    public function up(): void

{

    Schema::table('listings', function (Blueprint $table) {
        $table->string('item_condition');
        $table->text('description');
        $table->foreignId('hub_id')->constrained('hubs')->restrictOnDelete();
        $table->foreignId('region_id')->constrained('regions');
    });

}

    /**

     * Reverse the migrations.

     */

    public function down(): void
{
    Schema::table('listings', function (Blueprint $table) {
        $table->dropForeign(['hub_id']);
        $table->dropForeign(['region_id']);
        $table->dropColumn(['item_condition', 'description', 'hub_id', 'region_id']);
    });

}

};

