<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductStatus;

class ExpireProductStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-status:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate expired product statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    { 
        ProductStatus::whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['is_active' => 0]);


        $this->info('Expired product statuses deactivated successfully.');
    }
}
