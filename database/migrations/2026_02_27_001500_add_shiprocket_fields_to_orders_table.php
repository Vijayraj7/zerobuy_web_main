<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shiprocket_order_id')->nullable()->after('track_url');
            $table->string('shiprocket_shipment_id')->nullable()->after('shiprocket_order_id');
            $table->string('shiprocket_awb_code')->nullable()->after('shiprocket_shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shiprocket_order_id',
                'shiprocket_shipment_id',
                'shiprocket_awb_code',
            ]);
        });
    }
};
