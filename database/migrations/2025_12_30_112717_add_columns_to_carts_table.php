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
        Schema::table('carts', function (Blueprint $table) {
            //
            // $table->longText('bulk_prices')->nullable();
            $table->string('variant_id')->nullable();
            $table->string('bulk_item_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            //
            // $table->dropColumn('bulk_prices');
            $table->dropColumn('variant_id');
            $table->dropColumn('bulk_item_id');
        });
    }
};
