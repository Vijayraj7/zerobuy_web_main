<?php

use App\Enums\ReturnOderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $missingPendingRows = DB::table('return_orders as ro')
            ->leftJoin('return_order_status_timelines as t', function ($join) {
                $join->on('t.return_order_id', '=', 'ro.id')
                    ->where('t.status', '=', ReturnOderStatus::PENDING->value);
            })
            ->whereNull('t.id')
            ->select('ro.id', 'ro.created_at')
            ->get();

        if ($missingPendingRows->isEmpty()) {
            return;
        }

        $insertRows = $missingPendingRows->map(function ($row) {
            return [
                'return_order_id' => $row->id,
                'status' => ReturnOderStatus::PENDING->value,
                'changed_at' => $row->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        DB::table('return_order_status_timelines')->insert($insertRows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty: this is a data backfill migration.
    }
};
