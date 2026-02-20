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
        Schema::table('return_orders', function (Blueprint $table) {
            $table->string('ifsc')->nullable()->after('bank_account_number');
            $table->string('upi_id')->nullable()->after('ifsc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->dropColumn(['ifsc', 'upi_id']);
        });
    }
};
