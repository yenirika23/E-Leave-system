<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom name bawaan Laravel, ganti dengan yang lebih lengkap
            $table->dropColumn('name');

            // Data identitas karyawan
            $table->string('nik', 20)->unique()->after('id');       // NIK = username login
            $table->string('full_name')->after('nik');               // Nama lengkap
            $table->enum('gender', ['L', 'P'])->after('full_name'); // Jenis kelamin
            $table->date('birth_date')->nullable()->after('gender'); // Tanggal lahir
            $table->string('phone', 20)->nullable()->after('birth_date'); // Telepon

            // Data pekerjaan
            $table->string('position')->nullable()->after('phone');  // Jabatan/posisi
            $table->foreignId('department_id')                       // Relasi ke departemen
                  ->nullable()
                  ->constrained('departments')
                  ->onDelete('set null')
                  ->after('position');

            // Role pengguna dalam sistem
            $table->enum('role', ['hrd', 'atasan', 'karyawan'])
                  ->default('karyawan')
                  ->after('department_id');

            // Atasan dari karyawan ini (relasi ke user lain yang rolenya atasan)
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->after('role');

            // Status akun
            $table->boolean('is_active')->default(true)->after('supervisor_id');

            // Flag: apakah sudah ganti password? (wajib ganti saat pertama login)
            $table->boolean('must_change_password')->default(true)->after('is_active');

            // Tanggal mulai bekerja
            $table->date('join_date')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nik', 'full_name', 'gender', 'birth_date', 'phone',
                'position', 'department_id', 'role', 'supervisor_id',
                'is_active', 'must_change_password', 'join_date'
            ]);
            $table->string('name')->after('id'); // kembalikan kolom name
        });
    }
};