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

            $table->string('state', 100);
            $table->decimal('charge', 10, 2);

            $table->timestamps();

            // One state per delivery setting
            $table->unique(['delivery_setting_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_state_charges');
    }
};
