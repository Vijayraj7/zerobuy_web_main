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
            $table->decimal('gst', 5, 2)->nullable()->after('price');
        });

        // Backfill historical rows using current product tax percentage.
        DB::statement('
            UPDATE order_products op
            INNER JOIN products p ON p.id = op.product_id
            SET op.gst = COALESCE(p.tax_percentage, 0)
            WHERE op.gst IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('gst');
        });
    }
};
