<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();                                    // ID otomatis
            $table->string('name');                          // Nama departemen
            $table->string('code')->unique();                // Kode departemen (unik)
            $table->text('description')->nullable();         // Deskripsi (boleh kosong)
            $table->timestamps();                            // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};