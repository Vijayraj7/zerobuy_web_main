<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['address_id']);

            $table->foreign('address_id')
                ->references('id')
                ->on('order_addresses')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Schema::dropIfExists('address_id');
    }
};
