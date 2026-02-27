<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->string('delivery_provider')->nullable()->after('delivery_mode');
            $table->text('provider_api_key')->nullable()->after('delivery_provider');
            $table->text('provider_api_secret')->nullable()->after('provider_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_provider',
                'provider_api_key',
                'provider_api_secret',
            ]);
        });
    }
};