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
        Schema::table('shops', function (Blueprint $table) {
            $table->string('shop_code', 10)->unique()->nullable()->after('id');
        });

        DB::table('shops')
            ->whereNull('shop_code')
            ->orderBy('id')
            ->chunkById(100, function ($shops) {
                foreach ($shops as $shop) {
                    do {
                        $code = strtoupper(Str::random(10));
                    } while (DB::table('shops')->where('shop_code', $code)->exists());

                    DB::table('shops')
                        ->where('id', $shop->id)
                        ->update(['shop_code' => $code]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropUnique(['shop_code']);
            $table->dropColumn('shop_code');
        });
    }
};
