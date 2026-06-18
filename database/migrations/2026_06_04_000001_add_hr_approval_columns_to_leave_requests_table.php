<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('disetujui_oleh', ['atasan', 'hr'])
                  ->nullable()
                  ->after('status');

            $table->foreignId('id_approver')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->after('disetujui_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['id_approver']);
            $table->dropColumn(['disetujui_oleh', 'id_approver']);
        });
    }
};
