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
            $table->string('product_name')->nullable()->after('product_id');
            $table->integer('return_days')->nullable()->after('product_name');
        });
        $orderProducts = DB::table('order_products')
            ->whereNull('product_name')->whereNull('return_days')
            ->get();
        foreach ($orderProducts as $orderProduct) {
            $product = DB::table('products')->where('id', $orderProduct->product_id)->first();
            if ($product) {
                DB::table('order_products')
                    ->where('id', $orderProduct->id)
                    ->update([
                        'product_name' => $product->name,
                        'return_days' => $product->return_period,
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
            $table->dropColumn('product_name');
            $table->dropColumn('return_days');
        });
    }
};
