<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE advertisements MODIFY start_date DATETIME NULL');
            DB::statement('ALTER TABLE advertisements MODIFY end_date DATETIME NULL');
            DB::statement("UPDATE advertisements SET start_date = CONCAT(DATE(start_date), ' 00:00:00') WHERE TIME(start_date) = '00:00:00'");
            DB::statement("UPDATE advertisements SET end_date = CONCAT(DATE(end_date), ' 23:59:59') WHERE TIME(end_date) = '00:00:00'");
            return;
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->change();
        });

        DB::table('advertisements')
            ->select(['id', 'start_date', 'end_date'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $start = $row->start_date ? Carbon::parse($row->start_date) : null;
                    $end = $row->end_date ? Carbon::parse($row->end_date) : null;

                    $updates = [];

                    if ($start && $start->format('H:i:s') === '00:00:00') {
                        $updates['start_date'] = $start->startOfDay();
                    }

                    if ($end && $end->format('H:i:s') === '00:00:00') {
                        $updates['end_date'] = $end->endOfDay();
                    }

                    if (!empty($updates)) {
                        DB::table('advertisements')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE advertisements MODIFY start_date DATE NULL');
            DB::statement('ALTER TABLE advertisements MODIFY end_date DATE NULL');
            return;
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->change();
        });
    }
};
