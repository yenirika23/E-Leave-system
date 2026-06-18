<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_quotas', function (Blueprint $table) {
            // Tambahkan kolom untuk track akumulasi dan carry forward
            $table->boolean('is_automatic')->default(false)->after('remaining_quota');
            // Tahun dari mana kuota ini dibawa (untuk carry forward tracking)
            $table->integer('carried_over_from_year')->nullable()->after('is_automatic');
            // Berapa hari yang sudah hangus otomatis
            $table->decimal('expired_days', 5, 1)->default(0)->after('carried_over_from_year');
            
            // Index untuk performa query
            $table->index(['user_id', 'year']);
            $table->index(['user_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_quotas', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'year']);
            $table->dropIndex(['user_id', 'leave_type_id', 'year']);
            $table->dropColumn(['is_automatic', 'carried_over_from_year', 'expired_days']);
        });
    }
};
