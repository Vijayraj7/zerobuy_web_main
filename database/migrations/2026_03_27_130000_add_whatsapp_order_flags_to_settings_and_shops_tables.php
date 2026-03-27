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
            $table->boolean('whatsapp_order_enabled')->default(false)->after('online_payment');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('whatsapp_order_enabled')->default(false)->after('cash_on_delivery_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('whatsapp_order_enabled');
        });

        Schema::table('generate_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_order_enabled');
        });
    }
};
