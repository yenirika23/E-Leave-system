<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\LeaveQuota;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hrDept   = Department::where('code', 'HR')->first();
        $prodDept = Department::where('code', 'PROD')->first();

        // =============================================
        // 1. BUAT AKUN HRD
        // =============================================
        $hrd = User::create([
            'nik'                  => '10001',
            'full_name'            => 'Budi Santoso',
            'email'                => 'hrd@eleave.com',
            'gender'               => 'L',
            'birth_date'           => '1985-03-15',
            'phone'                => '081234567890',
            'position'             => 'HR Manager',
            'department_id'        => $hrDept->id,
            'role'                 => 'hrd',
            'is_active'            => true,
            'must_change_password' => false,   // HRD tidak perlu ganti password pertama kali
            'join_date'            => '2015-01-05',
            'password'             => Hash::make('eleave@2024'),  // Password khusus HRD
        ]);

        // =============================================
        // 2. BUAT AKUN ATASAN (Supervisor)
        // =============================================
        $atasan = User::create([
            'nik'                  => '10002',
            'full_name'            => 'Dewi Rahayu',
            'email'                => 'atasan@eleave.com',
            'gender'               => 'P',
            'birth_date'           => '1988-07-20',
            'phone'                => '082345678901',
            'position'             => 'Production Supervisor',
            'department_id'        => $prodDept->id,
            'role'                 => 'atasan',
            'is_active'            => true,
            'must_change_password' => true,    // Harus ganti password saat pertama login
            'join_date'            => '2017-06-01',
            'password'             => Hash::make('10002'),  // Password default = NIK
        ]);

        // =============================================
        // 3. BUAT AKUN KARYAWAN
        // =============================================
        $karyawan = User::create([
            'nik'                  => '10003',
            'full_name'            => 'Ahmad Fauzi',
            'email'                => 'karyawan@eleave.com',
            'gender'               => 'L',
            'birth_date'           => '1995-11-10',
            'phone'                => '083456789012',
            'position'             => 'Operator Produksi',
            'department_id'        => $prodDept->id,
            'role'                 => 'karyawan',
            'supervisor_id'        => $atasan->id,  // Atasannya adalah Dewi Rahayu
            'is_active'            => true,
            'must_change_password' => true,
            'join_date'            => '2022-03-14',
            'password'             => Hash::make('10003'),  // Password default = NIK
        ]);

        // =============================================
        // 4. BUAT KUOTA CUTI UNTUK SEMUA USER
        // =============================================
        $leaveTypes  = LeaveType::all();
        $currentYear = now()->year;
        $users       = [$hrd, $atasan, $karyawan];

        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                LeaveQuota::create([
                    'user_id'         => $user->id,
                    'leave_type_id'   => $leaveType->id,
                    'year'            => $currentYear,
                    'total_quota'     => $leaveType->default_quota,
                    'used_quota'      => 0,
                    'remaining_quota' => $leaveType->default_quota,
                ]);
            }
        }

        $this->command->info('✅ Users dan kuota cuti berhasil dibuat!');
        $this->command->info('📋 HRD       | NIK: 10001 | Password: eleave@2024');
        $this->command->info('📋 Atasan    | NIK: 10002 | Password: 10002 (ganti saat login)');
        $this->command->info('📋 Karyawan  | NIK: 10003 | Password: 10003 (ganti saat login)');
    }
}