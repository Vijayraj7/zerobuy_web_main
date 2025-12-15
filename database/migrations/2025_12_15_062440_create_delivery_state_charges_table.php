<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_state_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_setting_id')
                ->constrained('delivery_settings')
                ->cascadeOnDelete();

            // NEW: state_id (FK)
            $table->foreignId('state_id')
                ->constrained('states')
                ->cascadeOnDelete();

            // Keep state name (optional but useful)
            $table->string('state', 100);

            $table->decimal('charge', 10, 2);
            $table->timestamps();

            // Correct unique constraint
            $table->unique(
                ['delivery_setting_id', 'state_id'],
                'delivery_setting_state_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_state_charges');
    }
};
