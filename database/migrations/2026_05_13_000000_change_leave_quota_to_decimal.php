<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_quotas')) {
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `total_quota` DECIMAL(5,1) NOT NULL');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `used_quota` DECIMAL(5,1) NOT NULL DEFAULT 0.0');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `remaining_quota` DECIMAL(5,1) NOT NULL');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `expired_days` DECIMAL(5,1) NOT NULL DEFAULT 0.0');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_quotas')) {
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `total_quota` INT NOT NULL');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `used_quota` INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `remaining_quota` INT NOT NULL');
            DB::statement('ALTER TABLE `leave_quotas` MODIFY `expired_days` DECIMAL(5,1) NOT NULL DEFAULT 0.0');
        }
    }
};
