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
        Schema::table('business_categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('status');
        });
        // initialise sort_order from existing alphabetic order
        DB::statement('SET @row := 0');
        DB::statement('UPDATE business_categories SET sort_order = (@row := @row + 1) ORDER BY name ASC');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
