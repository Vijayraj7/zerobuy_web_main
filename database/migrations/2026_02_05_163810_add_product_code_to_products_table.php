<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_code', 10)->unique()->nullable()->after('id');
        });

        DB::table('products')
            ->whereNull('product_code')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    do {
                        $code = strtoupper(Str::random(10));
                    } while (DB::table('products')->where('product_code', $code)->exists());

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['product_code' => $code]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['product_code']);
            $table->dropColumn('product_code');
        });
    }
};
