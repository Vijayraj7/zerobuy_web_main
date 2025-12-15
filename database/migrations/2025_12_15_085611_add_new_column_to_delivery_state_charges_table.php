<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_state_charges', function (Blueprint $table) {

            // Add column
            $table->foreignId('state_id')
                ->after('delivery_setting_id')
                ->constrained('states')
                ->cascadeOnDelete();

            // Correct unique constraint
            $table->unique(
                ['delivery_setting_id', 'state_id'],
                'delivery_state_setting_state_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('delivery_state_charges', function (Blueprint $table) {

            // Drop unique first
            $table->dropUnique('delivery_state_setting_state_unique');

            // Drop FK + column
            $table->dropForeign(['state_id']);
            $table->dropColumn('state_id');
        });
    }
};
