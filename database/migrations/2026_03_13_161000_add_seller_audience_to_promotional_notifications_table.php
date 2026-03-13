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
        Schema::table('promotional_notifications', function (Blueprint $table) {
            $table->string('seller_audience')->nullable()->after('shop_id');
        });

        DB::table('promotional_notifications')
            ->where('send_to', 'seller')
            ->update([
                'seller_audience' => DB::raw("CASE WHEN shop_id IS NULL THEN 'all' ELSE 'shop' END"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotional_notifications', function (Blueprint $table) {
            $table->dropColumn('seller_audience');
        });
    }
};