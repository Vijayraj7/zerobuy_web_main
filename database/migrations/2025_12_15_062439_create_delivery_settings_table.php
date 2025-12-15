<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();

            // If you have shops or sellers
            $table->unsignedBigInteger('shop_id')->nullable();

            $table->enum('delivery_mode', [
                'amount_based',
                'state_wise',
                'manual',
            ]);

            $table->boolean('update_when_shipped')->default(false);

            $table->timestamps();

            // Optional index
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
