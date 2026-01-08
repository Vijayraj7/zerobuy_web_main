<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->cascadeOnDelete();

            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            //
            $table->dropColumn('state_id');
            $table->dropColumn('district_id');
        });
    }
};
