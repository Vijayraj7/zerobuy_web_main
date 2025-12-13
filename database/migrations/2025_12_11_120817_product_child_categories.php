<?php

// use App\Models\ChildCategory;
// use App\Models\Product;

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
        Schema::create('product_child_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('child_category_id')->index();
            $table->timestamps(); 
            // $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            // $table->foreignIdFor(ChildCategory::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_child_categories');
    }
};
