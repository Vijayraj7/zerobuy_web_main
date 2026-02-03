<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable();
        });

        // Update existing records with media_id from products table
        DB::statement('
            UPDATE order_products op
            INNER JOIN products p ON op.product_id = p.id
            SET op.media_id = p.media_id
            WHERE op.media_id IS NULL AND p.media_id IS NOT NULL
        ');

        Schema::table('order_products', function (Blueprint $table) {
            $table->foreign('media_id')->references('id')->on('media')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->dropColumn('media_id');
        });
    }
};
