<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LeaveQuotaService;

class UpdateMonthlyQuotaCommand extends Command
{
    protected $signature = 'leave-quota:update-monthly';
    protected $description = 'Update automatic leave quota monthly based on employee join date anniversary.';

    public function handle(LeaveQuotaService $quotaService)
    {
        $quotaService->updateMonthlyQuota();
        $this->info('Monthly leave quota update completed.');
        return 0;
    }
}
