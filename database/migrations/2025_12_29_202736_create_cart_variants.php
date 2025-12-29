<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_variants_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);

            $table->timestamps();

            $table->unique(['cart_id', 'product_variants_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_variants');
    }
};
