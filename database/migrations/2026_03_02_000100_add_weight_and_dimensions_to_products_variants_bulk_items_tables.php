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
            $table->unsignedInteger('weight')->default(0)->after('discount_price');
            $table->decimal('length', 10, 2)->nullable()->after('weight');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('weight')->default(0)->after('quantity');
            $table->decimal('length', 10, 2)->nullable()->after('weight');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
        });

        Schema::table('product_bulk_items', function (Blueprint $table) {
            $table->unsignedInteger('weight')->default(0)->after('selling_price');
            $table->decimal('length', 10, 2)->nullable()->after('weight');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bulk_items', function (Blueprint $table) {
            $table->dropColumn(['weight', 'length', 'width', 'height']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['weight', 'length', 'width', 'height']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight', 'length', 'width', 'height']);
        });
    }
};
