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
        Schema::table('ad_transactions', function (Blueprint $table) {
            $table->string('ad_transaction_id')->nullable()->after('id');
        });

        // Backfill existing rows with deterministic TXN-prefixed IDs.
        $ids = DB::table('ad_transactions')
            ->whereNull('ad_transaction_id')
            ->pluck('id');

        foreach ($ids as $id) {
            DB::table('ad_transactions')
                ->where('id', $id)
                ->update([
                    'ad_transaction_id' => 'TXN' . str_pad((string) $id, 10, '0', STR_PAD_LEFT),
                ]);
        }

        Schema::table('ad_transactions', function (Blueprint $table) {
            $table->unique('ad_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_transactions', function (Blueprint $table) {
            $table->dropUnique(['ad_transaction_id']);
            $table->dropColumn('ad_transaction_id');
        });
    }
};
