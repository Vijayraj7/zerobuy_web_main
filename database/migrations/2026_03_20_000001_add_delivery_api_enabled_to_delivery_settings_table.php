<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->boolean('delivery_api_enabled')->default(false)->after('delivery_mode');
        });

        DB::table('delivery_settings')
            ->whereNotNull('delivery_provider')
            ->update(['delivery_api_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn('delivery_api_enabled');
        });
    }
};
