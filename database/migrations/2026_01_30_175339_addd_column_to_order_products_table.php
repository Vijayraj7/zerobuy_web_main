<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            //
            $table->decimal('mrp')->nullable()->after('price');
        });
        $orderProducts = DB::table('order_products')
            ->whereNull('mrp')
            ->get();
        foreach ($orderProducts as $orderProduct) {
            $product = DB::table('products')->where('id', $orderProduct->product_id)->first();
            if ($product) {
                DB::table('order_products')
                    ->where('id', $orderProduct->id)
                    ->update([
                        'mrp' => $orderProduct->bulkItem?->mrp ?? $product->price,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            //
            $table->dropColumn('mrp');
        });
    }
};
