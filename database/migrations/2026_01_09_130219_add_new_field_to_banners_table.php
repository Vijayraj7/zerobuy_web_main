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
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('slider_position', ['top', 'center', 'bottom'])->nullable();
            $table->enum('slider_type', ['sub_category', 'child_category', 'product', 'shop'])->nullable();
            $table->string('slider_link')->nullable(); // stores ID based on slider_type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['business_category_id']);
            $table->dropColumn('business_category_id'); 
            $table->dropColumn('slider_position');
            $table->dropColumn('slider_type');
            $table->dropColumn('slider_link');
        });
    }
};
