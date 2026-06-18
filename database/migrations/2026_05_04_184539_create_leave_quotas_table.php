<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini menyimpan sisa cuti masing-masing karyawan per jenis cuti per tahun
        Schema::create('leave_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')                     // Milik karyawan siapa
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('leave_type_id')               // Jenis cuti apa
                  ->constrained('leave_types')
                  ->onDelete('cascade');
            $table->year('year');                            // Tahun berlaku
            $table->integer('total_quota');                  // Total kuota (hari)
            $table->integer('used_quota')->default(0);       // Sudah dipakai (hari)
            $table->integer('remaining_quota');              // Sisa (hari) = total - used
            $table->timestamps();

            // Satu karyawan hanya punya 1 baris per jenis cuti per tahun
            $table->unique(['user_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_quotas');
    }
};