<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Data departemen awal perusahaan
        $departments = [
            ['name' => 'Human Resources',  'code' => 'HR',   'description' => 'Departemen Sumber Daya Manusia'],
            ['name' => 'Produksi',          'code' => 'PROD', 'description' => 'Departemen Produksi Semikonduktor'],
            ['name' => 'Quality Control',   'code' => 'QC',   'description' => 'Departemen Pengawasan Kualitas'],
            ['name' => 'Engineering',       'code' => 'ENG',  'description' => 'Departemen Teknik'],
            ['name' => 'Finance',           'code' => 'FIN',  'description' => 'Departemen Keuangan'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}