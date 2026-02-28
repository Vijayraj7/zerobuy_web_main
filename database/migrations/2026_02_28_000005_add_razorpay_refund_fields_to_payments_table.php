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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('razorpay_refund_id')->nullable()->after('razorpay_signature');
            $table->string('razorpay_refund_status')->nullable()->after('razorpay_refund_id');
            $table->decimal('razorpay_refund_amount', 12, 2)->nullable()->after('razorpay_refund_status');
            $table->timestamp('razorpay_refunded_at')->nullable()->after('razorpay_refund_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_refund_id',
                'razorpay_refund_status',
                'razorpay_refund_amount',
                'razorpay_refunded_at',
            ]);
        });
    }
};
