<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'day_type')) {
                $table->enum('day_type', ['full', 'morning', 'afternoon'])
                      ->default('full')
                      ->after('total_days');
            }

            if (!Schema::hasColumn('leave_requests', 'document_path')) {
                $table->string('document_path')->nullable()->after('day_type');
            }

            if (!Schema::hasColumn('leave_requests', 'document_name')) {
                $table->string('document_name')->nullable()->after('document_path');
            }
        });

        DB::statement('ALTER TABLE `leave_requests` MODIFY `total_days` DECIMAL(5,1) NOT NULL');
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'document_name')) {
                $table->dropColumn('document_name');
            }

            if (Schema::hasColumn('leave_requests', 'document_path')) {
                $table->dropColumn('document_path');
            }

            if (Schema::hasColumn('leave_requests', 'day_type')) {
                $table->dropColumn('day_type');
            }
        });

        DB::statement('ALTER TABLE `leave_requests` MODIFY `total_days` INT NOT NULL');
    }
};
