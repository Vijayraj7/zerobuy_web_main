<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {

            $table->foreignId('order_variants_id')
                ->nullable()
                ->constrained('order_variants')
                ->cascadeOnDelete();

            $table->foreignId('order_bulk_items_id')
                ->nullable()
                ->constrained('order_bulk_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            //
            $table->dropColumn('order_variants_id');
            $table->dropColumn('order_bulk_items_id');
            // $table->dropColumn('bulk_item_id');
        });
    }
};
