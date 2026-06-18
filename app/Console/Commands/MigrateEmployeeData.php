<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveQuota;
use App\Services\LeaveQuotaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MigrateEmployeeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate NIKs, recalculate leave quotas and synchronize used/remaining quotas from approved history.';

    public function handle()
    {
        $this->info('Starting employee data migration...');

        DB::beginTransaction();

        try {
            $this->regenerateNiks();
            $this->recalculateQuotas();

            DB::commit();

            $this->info('Migration finished successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
            logger()->error('MigrationEmployeeData failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function regenerateNiks()
    {
        $this->info('Regenerating NIK for all users...');

        // Ambil semua user urut berdasarkan join_date lalu id (meniru urutan penyimpanan)
        $users = User::orderBy('join_date')->orderBy('id')->get();

        $groups = [];

        foreach ($users as $user) {
            if (!$user->join_date) {
                continue;
            }

            $ym = Carbon::parse($user->join_date)->format('ym');

            if (!isset($groups[$ym])) {
                $groups[$ym] = [];
            }

            $groups[$ym][] = $user;
        }

        $changed = 0;

        foreach ($groups as $ym => $usersInGroup) {
            $sequence = 1;

            foreach ($usersInGroup as $user) {
                $newNik = $ym . str_pad($sequence, 2, '0', STR_PAD_LEFT);

                // Pastikan unik: jika sudah dipakai (oleh user lain), lanjutkan nomor
                while (User::where('nik', $newNik)->where('id', '!=', $user->id)->exists()) {
                    $sequence++;
                    $newNik = $ym . str_pad($sequence, 2, '0', STR_PAD_LEFT);
                }

                if ($user->nik !== $newNik) {
                    $user->nik = $newNik;
                    $user->save();
                    $changed++;
                }

                $sequence++;
            }
        }

        $this->info("Regenerated NIK for {$changed} users.");
    }

    protected function recalculateQuotas()
    {
        $this->info('Recalculating leave quotas and synchronizing used/remaining based on approved history...');

        $service = app(LeaveQuotaService::class);

        $leaveType = LeaveType::where('code', 'CT')->first();

        if (!$leaveType) {
            $this->warn('Leave type CT (Cuti Tahunan) not found. Skipping quota recalculation.');
            return;
        }

        $oldQuotas = LeaveQuota::where('leave_type_id', $leaveType->id)
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('year'));

        LeaveQuota::where('leave_type_id', $leaveType->id)->delete();

        $users = User::where('is_active', true)
            ->whereNotNull('join_date')
            ->get();

        $reportRows = [];
        $processed = 0;

        foreach ($users as $user) {
            $firstAnniversary = $service->getFirstAnniversary($user);
            if (!$firstAnniversary || now()->lessThan($firstAnniversary)) {
                continue;
            }

            $currentPeriodStart = $service->getQuotaPeriodStartDate($user);
            if (!$currentPeriodStart) {
                continue;
            }

            $carryIn = 0;
            $previousPeriodYear = null;

            for ($year = $firstAnniversary->year; $year <= $currentPeriodStart->year; $year++) {
                $periodStart = $user->join_date->copy()->setYear($year);
                if ($periodStart->lessThan($firstAnniversary)) {
                    continue;
                }

                $periodEnd = $periodStart->copy()->addYear();
                $isCurrentPeriod = $year === $currentPeriodStart->year;
                $baseTotal = $isCurrentPeriod
                    ? $service->calculateAnnualLeaveQuota($user)
                    : 12 + 11;

                $expectedTotal = min($baseTotal + $carryIn, 12 + 11 + 10);

                $used = LeaveRequest::where('user_id', $user->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('status', 'disetujui')
                    ->whereBetween('start_date', [
                        $periodStart->toDateString(),
                        $periodEnd->subDay()->toDateString(),
                    ])
                    ->sum('total_days');

                $remaining = max(0, $expectedTotal - $used);
                $expiredDays = $isCurrentPeriod ? 0 : max(0, $remaining - 10);
                $carryOut = $isCurrentPeriod ? 0 : min($remaining, 10);
                $carriedOverFromYear = $carryIn > 0 ? $previousPeriodYear : null;

                // Validasi bahwa annual leave tidak akan dibuat dengan quota 0
                $service->validateAnnualLeaveQuota($user, $leaveType, $expectedTotal);

                LeaveQuota::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'total_quota' => $expectedTotal,
                    'used_quota' => $used,
                    'remaining_quota' => $remaining,
                    'is_automatic' => true,
                    'carried_over_from_year' => $carriedOverFromYear,
                    'expired_days' => $expiredDays,
                ]);

                if ($isCurrentPeriod) {
                    $oldQuota = $oldQuotas->get($user->id)?->get($year);
                    $reportRows[] = [
                        'user' => $user->full_name,
                        'join_date' => $user->join_date->format('d M Y'),
                        'quota_old' => $oldQuota ? $oldQuota->total_quota : 0,
                        'quota_new' => $expectedTotal,
                        'remaining_old' => $oldQuota ? $oldQuota->remaining_quota : 0,
                        'remaining_new' => $remaining,
                        'carry_over' => $carriedOverFromYear ? min($remaining, 10) : 0,
                        'expired_days' => $expiredDays,
                    ];
                }

                $carryIn = $carryOut;
                $previousPeriodYear = $year;
                $processed++;
            }
        }

        if (!empty($reportRows)) {
            $this->table(
                ['Nama User', 'Join Date', 'Quota Lama', 'Quota Baru', 'Remaining Lama', 'Remaining Baru', 'Carry Over', 'Expired Days'],
                array_map(fn($row) => [
                    $row['user'],
                    $row['join_date'],
                    $row['quota_old'],
                    $row['quota_new'],
                    $row['remaining_old'],
                    $row['remaining_new'],
                    $row['carry_over'],
                    $row['expired_days'],
                ], $reportRows)
            );
        }

        $this->info("Recalculated quotas for {$processed} periods.");
    }
}
