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
        // Check if id column doesn't exist, then add it
        if (!Schema::hasColumn('order_products', 'id')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->id()->first();
            });
        }
        
        // Set sequential IDs for existing records where id is null
        $orderProducts = DB::table('order_products')
            ->whereNull('id')
            ->select('order_id', 'product_id', 'quantity', 'color', 'size', 'unit', 'price', 'order_variants_id', 'order_bulk_items_id')
            ->get();
            
        if ($orderProducts->count() > 0) {
            // Get the maximum existing ID
            $maxId = DB::table('order_products')->max('id') ?? 0;
            $id = $maxId + 1;
            
            foreach ($orderProducts as $orderProduct) {
                DB::table('order_products')
                    ->where('order_id', $orderProduct->order_id)
                    ->where('product_id', $orderProduct->product_id)
                    ->where('quantity', $orderProduct->quantity)
                    ->whereRaw('COALESCE(color, "") = COALESCE(?, "")', [$orderProduct->color])
                    ->whereRaw('COALESCE(size, "") = COALESCE(?, "")', [$orderProduct->size])
                    ->whereRaw('COALESCE(unit, "") = COALESCE(?, "")', [$orderProduct->unit])
                    ->whereNull('id')
                    ->limit(1)
                    ->update(['id' => $id++]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
