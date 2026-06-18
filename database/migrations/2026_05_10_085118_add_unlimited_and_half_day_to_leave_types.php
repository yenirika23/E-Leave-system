<?php
// database/migrations/xxxx_add_unlimited_and_half_day_to_leave_types.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Flag: apakah kuota tidak terbatas? (untuk Cuti Sakit)
            // Jika true, karyawan tidak dibatasi jumlah hari
            $table->boolean('is_unlimited')->default(false)->after('is_active');

            // Flag: apakah mendukung setengah hari? (untuk UPL Half Day)
            $table->boolean('allow_half_day')->default(false)->after('is_unlimited');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            // Tambahan untuk UPL Half Day
            // 'full' = full day, 'morning' = pagi, 'afternoon' = siang
            $table->enum('day_type', ['full', 'morning', 'afternoon'])
                  ->default('full')
                  ->after('total_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['is_unlimited', 'allow_half_day']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('day_type');
        });
    }
};