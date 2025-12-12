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
        Schema::table('products', function (Blueprint $table) {
            $table->string('condition_status')->nullable()->after('name')->comment('New|Refurbished'); // New,Refurbished
            $table->integer('return_period')->nullable()->after('min_order_quantity'); // days
            $table->decimal('tax_percentage', 5, 2)->nullable()->after('discount_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('condition_status');
            $table->dropColumn('return_period');
            $table->dropColumn('tax_percentage');
        });
    }
};
