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
        Schema::table('shops', function (Blueprint $table) {
            $table->string('store_type')->after('name')->nullable(); 
            // $table->foreignId('bussiness_categories_id')->nullable()->after('banner_id'); 
            $table->string('phone_number', 20)->after('prefix')->nullable(); 
            $table->string('whatsapp_number', 20)->nullable()->after('phone_number')->nullable(); 
            $table->string('pincode', 10)->after('district')->nullable(); 
            $table->string('gst_number')->nullable()->after('gst')->nullable(); 
            $table->boolean('terms_condition_status')->default(false)->after('status')->nullable();
            $table->text('return_policy')->after('terms_condition_status')->nullable();
            $table->date('store_since')->nullable()->after('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'store_type',
                'bussiness_categories_id',
                'phone_number',
                'whatsapp_number',
                'pincode',
                'gst_number',
                'return_policy',
                'terms_condition_status',
                'store_since',
            ]);
        });
    }
};
