<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('generate_settings', function (Blueprint $table) {
            $table->string('seller_google_playstore_url')->nullable()->after('google_playstore_url');
            $table->string('seller_app_store_url')->nullable()->after('app_store_url');
            $table->unsignedInteger('seller_ios_min_build')->nullable()->after('seller_android_min_build');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generate_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seller_google_playstore_url',
                'seller_app_store_url',
                'seller_ios_min_build',
            ]);
        });
    }
};
