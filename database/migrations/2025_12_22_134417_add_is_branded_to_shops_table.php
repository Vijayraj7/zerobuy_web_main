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
            //
            $table->boolean('is_branded')->default(false)->after('status');
            $table->boolean('is_verified')->default(false)->after('is_branded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            //
            Schema::dropIfExists('is_branded');
            Schema::dropIfExists('is_verified');
        });
    }
};
