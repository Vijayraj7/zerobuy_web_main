<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_amount_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_setting_id')
                ->constrained('delivery_settings')
                ->cascadeOnDelete();

            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2);
            $table->decimal('charge', 10, 2);

            $table->timestamps();

            // Performance
            $table->index(['delivery_setting_id', 'min_amount', 'max_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_amount_rules');
    }
};
