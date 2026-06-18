<?php
// database/seeders/LeaveTypeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            // ─── CUTI TAHUNAN ─────────────────────────────
            [
                'name'           => 'Cuti Tahunan',
                'code'           => 'CT',
                'default_quota'  => 12,
                'unit'           => 'hari',
                'description'    => 'Cuti rutin tahunan karyawan',
                'is_active'      => true,
                'is_unlimited'   => false,  // Ada batas
                'allow_half_day' => false,
            ],

            // ─── CUTI SAKIT (UNLIMITED) ────────────────────
            [
                'name'           => 'Cuti Sakit',
                'code'           => 'CS',
                'default_quota'  => 0,      // 0 = tidak terbatas
                'unit'           => 'hari',
                'description'    => 'Cuti sakit — tidak ada batas waktu, wajib lampirkan surat dokter',
                'is_active'      => true,
                'is_unlimited'   => true,   // ← UNLIMITED: tidak ada batas hari
                'allow_half_day' => false,
            ],

            // ─── CUTI MELAHIRKAN ──────────────────────────
            [
                'name'           => 'Cuti Melahirkan',
                'code'           => 'CM',
                'default_quota'  => 90,
                'unit'           => 'hari',
                'description'    => 'Cuti melahirkan bagi karyawan perempuan (3 bulan)',
                'is_active'      => true,
                'is_unlimited'   => false,
                'allow_half_day' => false,
            ],

            // ─── CUTI DUKA ────────────────────────────────
            [
                'name'           => 'Cuti Duka',
                'code'           => 'CD',
                'default_quota'  => 3,
                'unit'           => 'hari',
                'description'    => 'Cuti karena anggota keluarga inti meninggal dunia',
                'is_active'      => true,
                'is_unlimited'   => false,
                'allow_half_day' => false,
            ],

            // ─── CUTI PERNIKAHAN ──────────────────────────
            [
                'name'           => 'Cuti Pernikahan',
                'code'           => 'CP',
                'default_quota'  => 3,
                'unit'           => 'hari',
                'description'    => 'Cuti pernikahan karyawan',
                'is_active'      => true,
                'is_unlimited'   => false,
                'allow_half_day' => false,
            ],

            // ─── UPL FULL DAY ─────────────────────────────
            [
                'name'           => 'UPL Full Day',
                'code'           => 'UPLF',
                'default_quota'  => 6,      // 6 hari per tahun
                'unit'           => 'hari',
                'description'    => 'Unpaid Leave (UPL) — izin tidak masuk satu hari penuh tanpa gaji',
                'is_active'      => true,
                'is_unlimited'   => false,
                'allow_half_day' => false,
            ],

            // ─── UPL HALF DAY ─────────────────────────────
            [
                'name'           => 'UPL Half Day',
                'code'           => 'UPLH',
                'default_quota'  => 12,     // 12 sesi (pagi/siang) per tahun
                'unit'           => 'sesi',
                'description'    => 'Unpaid Leave setengah hari — pilih sesi pagi (08.00-12.00) atau siang (13.00-17.00)',
                'is_active'      => true,
                'is_unlimited'   => false,
                'allow_half_day' => true,   // ← Bisa pilih pagi atau siang
            ],
        ];

        foreach ($leaveTypes as $type) {
            // updateOrCreate agar tidak duplikat jika sudah ada
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}