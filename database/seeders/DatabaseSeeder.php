<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan PENTING: Department dulu, baru LeaveType, baru User
        $this->call([
            DepartmentSeeder::class,   // 1. Buat departemen dulu
            LeaveTypeSeeder::class,    // 2. Buat jenis cuti
            UserSeeder::class,         // 3. Baru buat user (butuh department & leave_types)
        ]);
    }
}