<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_variants', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('order_products_id')
            //     ->constrained('order_products')
            //     ->cascadeOnDelete();

            // $table->unsignedBigInteger('product_id')->index();

            $table->string('color_name');
            $table->string('color_code');
            $table->string('size_name');
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('quantity')->default(0);
            // $table->timestamps();

            // $table->foreignId('product_variants_id')
            //     ->constrained('product_variants')
            //     ->cascadeOnDelete();

            // $table->integer('quantity')->default(1);
            // $table->decimal('price', 10, 2);

            // $table->timestamps();

            // $table->unique(['cart_id', 'product_variants_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_variants');
    }
};
