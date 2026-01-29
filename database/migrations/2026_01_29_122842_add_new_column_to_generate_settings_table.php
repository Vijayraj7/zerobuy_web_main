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
        Schema::table('generate_settings', function (Blueprint $table) {
            $table->boolean('partial_payment')->default(false);
            $table->integer('partial_payment_percentage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generate_settings', function (Blueprint $table) {
            $table->dropColumn('partial_payment');
            $table->dropColumn('partial_payment_percentage');
        });
    }
};
