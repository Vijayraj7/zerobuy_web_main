<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_bulk_items', function (Blueprint $table) {
            $table->id();

            // $table->foreignId('order_id')
            //     ->constrained()
            //     ->cascadeOnDelete();


            // $table->id();
            // $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->integer('quantity');
            $table->integer('moq');
            $table->decimal('mrp', 10, 2);
            $table->decimal('selling_price', 10, 2);

            // $table->foreignId('product_bulk_items_id')
            //     ->constrained('product_bulk_items')
            //     ->cascadeOnDelete();

            // $table->integer('quantity');
            // $table->decimal('price', 10, 2);

            // $table->timestamps();
            // $table->unique(['cart_id', 'product_bulk_items_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_bulk_items');
    }
};
