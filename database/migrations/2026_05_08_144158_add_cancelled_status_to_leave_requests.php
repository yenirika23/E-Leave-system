<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan nilai 'dibatalkan' ke kolom status
        \DB::statement("
            ALTER TABLE leave_requests
            MODIFY COLUMN status
            ENUM('menunggu','disetujui','ditolak','dibatalkan')
            NOT NULL DEFAULT 'menunggu'
        ");
    }

    public function down(): void
    {
        \DB::statement("
            ALTER TABLE leave_requests
            MODIFY COLUMN status
            ENUM('menunggu','disetujui','ditolak')
            NOT NULL DEFAULT 'menunggu'
        ");
    }
};