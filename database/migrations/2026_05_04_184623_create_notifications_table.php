<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')                     // Notifikasi untuk siapa
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->string('title');                         // Judul notifikasi
            $table->text('message');                         // Isi pesan
            $table->string('type')->default('info');         // Tipe: info, success, warning
            $table->boolean('is_read')->default(false);      // Sudah dibaca?
            $table->foreignId('leave_request_id')            // Terkait pengajuan mana
                  ->nullable()
                  ->constrained('leave_requests')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};