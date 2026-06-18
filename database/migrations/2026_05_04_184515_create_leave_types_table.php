<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nama jenis cuti (ex: Cuti Tahunan)
            $table->string('code', 10)->unique();            // Kode (ex: CT, CS, CM)
            $table->integer('default_quota');                // Kuota default per tahun (hari)
            $table->string('unit')->default('hari');         // Satuan (hari)
            $table->text('description')->nullable();         // Keterangan tambahan
            $table->boolean('is_active')->default(true);     // Apakah jenis cuti aktif?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};