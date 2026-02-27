<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `delivery_settings` MODIFY `delivery_mode` ENUM('amount_based','state_wise','manual','provider_api') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `delivery_settings` MODIFY `delivery_mode` ENUM('amount_based','state_wise','manual') NOT NULL");
    }
};
