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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('api_provider')->nullable()->after('payment_method');
            $table->string('provider_order_id')->nullable()->after('api_provider');
            $table->string('provider_shipment_id')->nullable()->after('provider_order_id');
            $table->string('provider_awb_code')->nullable()->after('provider_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'api_provider',
                'provider_order_id',
                'provider_shipment_id',
                'provider_awb_code',
            ]);
        });
    }
};
