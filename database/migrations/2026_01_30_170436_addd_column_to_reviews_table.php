<?php

use App\Models\OrderProduct;
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
        Schema::table('reviews', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('order_product_id')->nullable()->after('customer_id');
        });
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('order_product_id')->references('id')->on('order_products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            //
            $table->dropForeign(['order_product_id']);
            $table->dropColumn('order_product_id');
        });
    }
};
