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
        Schema::create('promotional_notifications', function (Blueprint $table) { 
            $table->id();
            $table->enum('send_to', ['user','seller']);
            $table->unsignedBigInteger('business_category_id');
            $table->string('notification_option_type')->nullable(); 
            $table->unsignedBigInteger('notification_option_link')->nullable(); 
            $table->unsignedBigInteger('shop_id')->nullable(); 
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('message')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_notifications');
    }
};
