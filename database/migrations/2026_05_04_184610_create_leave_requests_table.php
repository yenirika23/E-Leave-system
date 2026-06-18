<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel utama: semua pengajuan cuti disimpan di sini
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            // Siapa yang mengajukan
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Jenis cuti apa yang diajukan
            $table->foreignId('leave_type_id')
                  ->constrained('leave_types')
                  ->onDelete('cascade');

            // Tanggal pengajuan dan periode cuti
            $table->date('request_date');                    // Tanggal saat mengajukan
            $table->date('start_date');                      // Tanggal mulai cuti
            $table->date('end_date');                        // Tanggal selesai cuti
            $table->integer('total_days');                   // Total hari cuti

            // Alasan dan keterangan
            $table->text('reason');                          // Alasan cuti
            $table->text('notes')->nullable();               // Catatan tambahan

            // Status pengajuan
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])
                  ->default('menunggu');

            // Siapa atasan yang memproses (menyetujui/menolak)
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamp('approved_at')->nullable();    // Kapan diproses
            $table->text('rejection_reason')->nullable();    // Alasan penolakan (jika ditolak)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};