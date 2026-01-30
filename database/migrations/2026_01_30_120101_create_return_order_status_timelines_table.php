<?php

use App\Models\ReturnOrder;
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
        Schema::create('return_order_status_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ReturnOrder::class)->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->unique(['return_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_order_status_timelines');
    }
};
