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
        Schema::table('return_orders', function (Blueprint $table) {
            $table->string('return_code', 10)->unique()->nullable()->after('id');
        });

        DB::table('return_orders')
            ->whereNull('return_code')
            ->orderBy('id')
            ->chunkById(100, function ($returnOrders) {
                foreach ($returnOrders as $returnOrder) {
                    do {
                        $code = strtoupper(Str::random(10));
                    } while (DB::table('return_orders')->where('return_code', $code)->exists());

                    DB::table('return_orders')
                        ->where('id', $returnOrder->id)
                        ->update(['return_code' => $code]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->dropUnique(['return_code']);
            $table->dropColumn('return_code');
        });
    }
};
