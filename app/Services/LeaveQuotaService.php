<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveQuotaService
{
    /**
     * Konstanta untuk aturan cuti
     */
    private const MONTHS_BEFORE_ENTITLED = 12;           // 1 tahun
    private const MONTHLY_QUOTA_INCREMENT = 1;           // Hari per bulan
    private const MAX_CARRY_FORWARD_DAYS = 10;           // Max carry forward
    private const FULL_DAY_DEDUCTION = 1;                // Pemotongan full day
    private const HALF_DAY_DEDUCTION = 0.5;              // Pemotongan half day

    /**
     * Validasi sebelum menyimpan record leave_quota annual leave (CT).
     * Memastikan user yang eligible untuk annual leave tidak memiliki total_quota = 0.
     *
     * @param User $user
     * @param LeaveType $leaveType
     * @param float $totalQuota
     * @return bool true jika valid, exception jika tidak
     * @throws \Exception jika attempt menyimpan CT dengan total_quota 0 untuk user yang entitled
     */
    public function validateAnnualLeaveQuota(User $user, LeaveType $leaveType, float $totalQuota): bool
    {
        // Hanya validasi untuk cuti tahunan (CT)
        if ($leaveType->code !== 'CT') {
            return true;
        }

        // Jika user sudah berhak mendapat annual leave
        if ($this->isEntitledToAnnualLeave($user)) {
            // Total quota HARUS lebih dari 0
            if ($totalQuota <= 0) {
                $yearsOfService = $user->join_date ? $user->join_date->diffInMonths(now()) : 0;
                $message = sprintf(
                    "GUARD VIOLATION: Cannot save annual leave (CT) with zero or negative total_quota for entitled user. " .
                    "User: %s (ID: %d), Join: %s, Years of Service: %d months, Attempted quota: %s",
                    $user->full_name,
                    $user->id,
                    $user->join_date?->toDateString() ?? 'N/A',
                    $yearsOfService,
                    $totalQuota
                );
                \Log::error($message);
                throw new \Exception($message);
            }
        }

        return true;
    }

    /**
     * Cek apakah karyawan sudah berhak mendapat cuti tahunan
     * (Sudah bekerja 1 tahun atau lebih)
     */
    public function isEntitledToAnnualLeave(User $user): bool
    {
        if (!$user->join_date) {
            return false;
        }

        $yearsOfService = $user->join_date->diffInMonths(now());
        return $yearsOfService >= self::MONTHS_BEFORE_ENTITLED;
    }

    /**
     * Hitung berapa bulan karyawan sudah layak mendapat cuti
     * Gunakan untuk menambah kuota bertahap setiap bulan
     */
    public function getQuotaPeriodStartDate(User $user, Carbon $date = null): ?Carbon
    {
        if (!$user->join_date) {
            return null;
        }

        $date = $date ? Carbon::parse($date) : now();
        $firstAnniversary = $user->join_date->copy()->addYear();

        if ($date->lessThan($firstAnniversary)) {
            return null;
        }

        $yearsSinceJoin = $user->join_date->diffInYears($date);
        $periodStart = $user->join_date->copy()->addYears($yearsSinceJoin);

        if ($periodStart->greaterThan($date)) {
            $periodStart->subYear();
        }

        return $periodStart;
    }

    public function getQuotaPeriodEndDate(User $user, Carbon $date = null): ?Carbon
    {
        $periodStart = $this->getQuotaPeriodStartDate($user, $date);
        return $periodStart ? $periodStart->copy()->addYear() : null;
    }

    public function getFirstAnniversary(User $user): ?Carbon
    {
        if (!$user->join_date) {
            return null;
        }

        return $user->join_date->copy()->addYear();
    }

    public function getNextAnniversary(User $user, Carbon $date = null): ?Carbon
    {
        if (!$user->join_date) {
            return null;
        }

        $date = $date ? Carbon::parse($date) : now();
        $firstAnniversary = $this->getFirstAnniversary($user);

        if ($date->lessThan($firstAnniversary)) {
            return $firstAnniversary;
        }

        $periodStart = $this->getQuotaPeriodStartDate($user, $date);
        if (!$periodStart) {
            return null;
        }

        $nextAnniversary = $periodStart->copy()->addYear();

        if (!$nextAnniversary->greaterThan($date)) {
            $nextAnniversary->addYear();
        }

        return $nextAnniversary;
    }

    public function getDaysUntilNextAnniversary(User $user, Carbon $date = null): ?int
    {
        $nextAnniversary = $this->getNextAnniversary($user, $date);
        if (!$nextAnniversary) {
            return null;
        }

        return Carbon::parse($date ?? now())->diffInDays($nextAnniversary, false);
    }

    public function getAnnualLeaveExpiryInfo(User $user, Carbon $date = null): ?array
    {
        $date = $date ? Carbon::parse($date) : now();
        $annualLeaveType = LeaveType::where('code', 'CT')->first();

        if (!$annualLeaveType || !$this->isEntitledToAnnualLeave($user)) {
            return null;
        }

        $periodYear = $this->getQuotaPeriodYear($user, $date);
        if (!$periodYear) {
            return null;
        }

        $quota = $this->getOrCreateQuota($user, $annualLeaveType, $periodYear);
        if ($quota->remaining_quota <= self::MAX_CARRY_FORWARD_DAYS) {
            return null;
        }

        $nextAnniversary = $this->getNextAnniversary($user, $date);
        if (!$nextAnniversary) {
            return null;
        }

        $daysUntil = $date->diffInDays($nextAnniversary, false);
        if ($daysUntil > 90) {
            return null;
        }

        return [
            'total_remaining' => $quota->remaining_quota,
            'expired_days' => max(0, $quota->remaining_quota - self::MAX_CARRY_FORWARD_DAYS),
            'next_anniversary' => $nextAnniversary,
            'days_until_expiry' => $daysUntil,
            'carry_over' => min($quota->remaining_quota, self::MAX_CARRY_FORWARD_DAYS),
            'total_quota' => $quota->total_quota,
            'used_quota' => $quota->used_quota,
            'year' => $periodYear,
        ];
    }

    public function getQuotaPeriodYear(User $user, Carbon $date = null): ?int
    {
        $periodStart = $this->getQuotaPeriodStartDate($user, $date);
        return $periodStart ? $periodStart->year : null;
    }

    public function calculateAnnualLeaveQuota(User $user, Carbon $date = null): float
    {
        $date = $date ? Carbon::parse($date) : now();
        $periodStart = $this->getQuotaPeriodStartDate($user, $date);

        if (!$periodStart) {
            return 0;
        }

        $monthsSincePeriodStart = $periodStart->diffInMonths($date);
        return 12 + $monthsSincePeriodStart;
    }

    public function getPreviousPeriodCarryOver(User $user, LeaveType $leaveType, Carbon $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();
        $currentPeriodStart = $this->getQuotaPeriodStartDate($user, $date);

        if (!$currentPeriodStart) {
            return [
                'carry' => 0,
                'expired' => 0,
                'from_year' => null,
                'previous_total' => 0,
                'used' => 0,
            ];
        }

        $firstAnniversary = $this->getFirstAnniversary($user);
        $previousPeriodStart = $currentPeriodStart->copy()->subYear();

        if ($previousPeriodStart->lessThan($firstAnniversary)) {
            return [
                'carry' => 0,
                'expired' => 0,
                'from_year' => null,
                'previous_total' => 0,
                'used' => 0,
            ];
        }

        $previousTotal = 12 + 11;

        $used = LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'disetujui')
            ->whereBetween('start_date', [
                $previousPeriodStart->toDateString(),
                $currentPeriodStart->copy()->subDay()->toDateString(),
            ])
            ->sum('total_days');

        $remaining = max(0, $previousTotal - $used);
        $carry = min($remaining, self::MAX_CARRY_FORWARD_DAYS);
        $expired = max(0, $remaining - self::MAX_CARRY_FORWARD_DAYS);

        return [
            'carry' => $carry,
            'expired' => $expired,
            'from_year' => $previousPeriodStart->year,
            'previous_total' => $previousTotal,
            'used' => $used,
        ];
    }

    /**
     * Ambil atau buat kuota cuti untuk tahun tertentu
     */
    public function getOrCreateQuota(User $user, LeaveType $leaveType, $year = null): LeaveQuota
    {
        $year = $year ?? $this->getQuotaPeriodYear($user) ?? now()->year;

        $quota = LeaveQuota::firstOrCreate(
            [
                'user_id'        => $user->id,
                'leave_type_id'  => $leaveType->id,
                'year'           => $year,
            ],
            [
                'total_quota'     => 0.0,
                'used_quota'      => 0.0,
                'remaining_quota' => 0.0,
                'is_automatic'    => false,
                'opening_balance' => 0.0,
            ]
        );

        if ($leaveType->code === 'CT' && $this->isEntitledToAnnualLeave($user)) {
            $carryData = $this->getPreviousPeriodCarryOver($user, $leaveType);
            $expectedTotal = $this->calculateAnnualLeaveQuota($user) + $carryData['carry'];
            $expectedTotal += $quota->opening_balance ?? 0;
            $expectedTotal = min($expectedTotal, 12 + self::MAX_CARRY_FORWARD_DAYS + 11);

            if ($quota->total_quota !== $expectedTotal) {
                // Validasi sebelum menyimpan bahwa quota tidak akan menjadi 0
                $this->validateAnnualLeaveQuota($user, $leaveType, $expectedTotal);

                $delta = $expectedTotal - $quota->total_quota;
                $quota->total_quota = $expectedTotal;
                $quota->remaining_quota = max(0, $quota->remaining_quota + $delta);
                $quota->carried_over_from_year = $carryData['carry'] > 0 ? $carryData['from_year'] : null;
                $quota->is_automatic = true;
                $quota->save();
            }
        }

        return $quota;
    }

    /**
     * Update kuota bulanan otomatis untuk karyawan yang sudah 1 tahun
     * Dipanggil oleh artisan command setiap bulan
     */
    public function updateMonthlyQuota(): void
    {
        $year = now()->year;
        $allLeaveTypes = LeaveType::where('is_active', true)
            ->where('is_unlimited', false)
            ->get();

        // Ambil semua user aktif yang punya tanggal join dan sudah berhak
        $eligibleUsers = User::where('is_active', true)
            ->whereNotNull('join_date')
            ->get()
            ->filter(fn($u) => $this->isEntitledToAnnualLeave($u));

        foreach ($eligibleUsers as $user) {
            foreach ($allLeaveTypes as $leaveType) {
                // Hanya untuk "Cuti Tahunan" (Annual Leave)
                if ($leaveType->code !== 'CT') {
                    continue;
                }

                $periodYear = $this->getQuotaPeriodYear($user);
                if (!$periodYear) {
                    continue;
                }

                $quota = $this->getOrCreateQuota($user, $leaveType, $periodYear);

                // Hitung berapa hari kuota yang seharusnya ada pada periode saat ini,
                // termasuk carry over maksimum dari periode sebelumnya.
                $carryData = $this->getPreviousPeriodCarryOver($user, $leaveType);
                $expectedTotal = $this->calculateAnnualLeaveQuota($user) + $carryData['carry'];
                $expectedTotal += $quota->opening_balance ?? 0;
                $expectedTotal = min($expectedTotal, 12 + self::MAX_CARRY_FORWARD_DAYS + 11);

                if ($quota->total_quota !== $expectedTotal || $quota->carried_over_from_year !== $carryData['from_year']) {
                    // Validasi sebelum menyimpan bahwa quota tidak akan menjadi 0
                    $this->validateAnnualLeaveQuota($user, $leaveType, $expectedTotal);

                    $delta = $expectedTotal - $quota->total_quota;
                    $quota->total_quota = $expectedTotal;
                    $quota->remaining_quota = max(0, $quota->remaining_quota + $delta);
                    $quota->carried_over_from_year = $carryData['carry'] > 0 ? $carryData['from_year'] : null;

                    if ($quota->used_quota > $quota->total_quota) {
                        $quota->used_quota = $quota->total_quota;
                        $quota->remaining_quota = 0;
                    }

                    $quota->is_automatic = true;
                    $quota->save();
                }
            }
        }
    }

    /**
     * Proses carry forward dan hangus cuti di akhir tahun
     * Dipanggil 1 Januari atau manual untuk tahun sebelumnya
     */
    public function processYearEndCloseout($periodStartYear = null): array
    {
        $periodStartYear = $periodStartYear ?? now()->subYear()->year;
        $report = [
            'processed_count' => 0,
            'expired_total' => 0,
        ];

        $nextPeriodStartYear = $periodStartYear + 1;

        // Ambil semua kuota cuti tahunan untuk periode yang diminta
        $quotas = LeaveQuota::whereHas('leaveType', function ($q) {
            $q->where('code', 'CT'); // Hanya cuti tahunan
        })
        ->where('year', $periodStartYear)
        ->get();

        foreach ($quotas as $quota) {
            $user = $quota->user;
            if (!$user || !$user->join_date) {
                continue;
            }

            $periodStart = $user->join_date->copy()->setYear($periodStartYear);
            $periodEnd = $periodStart->copy()->addYear();

            if (now()->lessThan($periodEnd)) {
                continue;
            }

            $remainingAfterExpiry = $quota->remaining_quota;
            $expiredDays = 0;

            if ($remainingAfterExpiry > self::MAX_CARRY_FORWARD_DAYS) {
                $expiredDays = $remainingAfterExpiry - self::MAX_CARRY_FORWARD_DAYS;
            }

            $quota->expired_days = $expiredDays;
            $report['expired_total'] += $expiredDays;
            $quota->save();
            $report['processed_count']++;
        }

        return $report;
    }

    /**
     * Potong kuota saat cuti disetujui
     */
    public function deductQuotaOnApproval($user, $leaveType, $dayType, $totalDays, $referenceDate = null): bool
    {
        // Jika unlimited, jangan potong
        if ($leaveType->is_unlimited) {
            return true;
        }

        // Jika cuti tahunan belum berhak, pengajuan masih boleh dibuat, tetapi tidak memotong kuota
        if ($leaveType->code === 'CT' && !$this->isEntitledToAnnualLeave($user)) {
            return true;
        }

        $deduction = $this->calculateDeduction($dayType, $totalDays);

        $referenceDate = $referenceDate ? Carbon::parse($referenceDate) : now();
        $year = $this->getQuotaPeriodYear($user, $referenceDate) ?? now()->year;

        $quota = LeaveQuota::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (!$quota || $quota->remaining_quota < $deduction) {
            return false;
        }

        $quota->used_quota += $deduction;
        $quota->remaining_quota -= $deduction;
        $quota->save();

        return true;
    }

    /**
     * Kembalikan kuota saat cuti ditolak/dibatalkan
     */
    public function restoreQuotaOnRejection($user, $leaveType, $dayType, $totalDays, $referenceDate = null): bool
    {
        if ($leaveType->is_unlimited) {
            return true;
        }

        if ($leaveType->code === 'CT' && !$this->isEntitledToAnnualLeave($user)) {
            return true;
        }

        $deduction = $this->calculateDeduction($dayType, $totalDays);

        $referenceDate = $referenceDate ? Carbon::parse($referenceDate) : now();
        $year = $this->getQuotaPeriodYear($user, $referenceDate) ?? now()->year;

        $quota = LeaveQuota::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (!$quota) {
            return false;
        }

        $quota->used_quota = max(0, $quota->used_quota - $deduction);
        $quota->remaining_quota = min(
            $quota->total_quota,
            $quota->remaining_quota + $deduction
        );
        $quota->save();

        return true;
    }

    /**
     * Hitung deduction berdasarkan tipe hari
     */
    private function calculateDeduction($dayType, $totalDays = 1): float
    {
        if ($dayType === 'full' || $dayType === 'full-day') {
            return self::FULL_DAY_DEDUCTION * $totalDays;
        } elseif ($dayType === 'morning' || $dayType === 'afternoon') {
            return self::HALF_DAY_DEDUCTION;
        }

        return $totalDays;
    }

    /**
     * Ambil ringkasan kuota user
     */
    public function getQuotaSummary(User $user, $year = null): array
    {
        $year = $year ?? now()->year;

        $quotas = LeaveQuota::where('user_id', $user->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        $summary = [
            'year'           => $year,
            'is_entitled'    => $this->isEntitledToAnnualLeave($user),
            'days_of_service' => $user->join_date ? $user->join_date->diffInMonths(now()) : 0,
            'quotas'         => [],
        ];

        foreach ($quotas as $quota) {
            $summary['quotas'][] = [
                'name'              => $quota->leaveType->name,
                'code'              => $quota->leaveType->code,
                'total_quota'       => $quota->total_quota,
                'used_quota'        => $quota->used_quota,
                'remaining_quota'   => $quota->remaining_quota,
                'is_automatic'      => $quota->is_automatic,
                'carried_over'      => $quota->carried_over_from_year,
                'expired_days'      => $quota->expired_days,
            ];
        }

        return $summary;
    }

    /**
     * Hitung eligible kuota (yang sudah dilacak dalam bulan ini)
     * Untuk karyawan baru yang belum 1 tahun, return 0
     * Untuk yang sudah 1 tahun, return kuota yang sudah diberikan tahun ini
     */
    public function getEligibleQuota(User $user, LeaveType $leaveType, $date = null): float
    {
        if (!$this->isEntitledToAnnualLeave($user)) {
            return 0;
        }

        if ($leaveType->code !== 'CT') {
            return $leaveType->default_quota;
        }

        $date = $date ? Carbon::parse($date) : now();
        $periodYear = $this->getQuotaPeriodYear($user, $date);
        $quota = $this->getOrCreateQuota($user, $leaveType, $periodYear);
        return $quota->total_quota;
    }
}
