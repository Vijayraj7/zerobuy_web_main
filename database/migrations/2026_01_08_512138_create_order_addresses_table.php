<?php

use App\Models\Customer;
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
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->foreignIdFor(Customer::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('address_type')->nullable();
            $table->string('area')->nullable();
            $table->string('road_no')->nullable();
            $table->string('flat_no')->nullable();
            $table->string('house_no')->nullable();
            $table->string('address_line')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('post_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('state')->nullable();
            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->cascadeOnDelete();

            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
