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
            $table->string('bulk_price_id')->nullable();
            $table->string('variant_id')->nullable();
            $table->longText('bulk_items')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            //
            $table->dropColumn('bulk_price_id');
            $table->dropColumn('variant_id');
            $table->dropColumn('bulk_items');
        });
    }
};
