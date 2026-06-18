<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Services\LeaveQuotaService;
use Carbon\Carbon;

$service = app(LeaveQuotaService::class);
$leaveType = LeaveType::where('code', 'CT')->first();
$today = Carbon::now();

if (!$leaveType) {
    echo "Leave type CT not found.\n";
    exit(1);
}

$users = User::whereNotNull('join_date')->get();

$rows = [];
$anomalies = [];

foreach ($users as $user) {
    $periodYear = $service->getQuotaPeriodYear($user, $today);
    $periodStart = $periodYear ? Carbon::parse($user->join_date)->copy()->setYear($periodYear) : null;
    $periodEnd = $periodStart ? $periodStart->copy()->addYear()->subDay() : null;
    $expectedTotal = $service->calculateAnnualLeaveQuota($user, $today);

    $quota = $user->leaveQuotas()
        ->where('leave_type_id', $leaveType->id)
        ->where('year', $periodYear)
        ->first();

    $approvedUsed = LeaveRequest::where('user_id', $user->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('status', 'disetujui')
        ->whereBetween('start_date', [
            $periodStart ? $periodStart->toDateString() : '0000-01-01',
            $periodEnd ? $periodEnd->toDateString() : '9999-12-31'
        ])
        ->sum('total_days');

    $carryAmount = 0;
    if ($quota && $periodStart) {
        $carryAmount = max(0, $quota->total_quota - $expectedTotal);
    }

    $rows[] = [
        'user' => $user->full_name,
        'role' => $user->role,
        'join_date' => $user->join_date->toDateString(),
        'period_year' => $periodYear ?? '-',
        'period_start' => $periodStart ? $periodStart->toDateString() : '-',
        'period_end' => $periodEnd ? $periodEnd->toDateString() : '-',
        'expected_total' => $expectedTotal,
        'quota_total' => $quota ? $quota->total_quota : 0,
        'used' => $quota ? $quota->used_quota : 0,
        'remaining' => $quota ? $quota->remaining_quota : 0,
        'approved_used' => $approvedUsed,
        'carry_over' => $carryAmount,
        'carry_from_year' => $quota ? ($quota->carried_over_from_year ?: '-') : '-',
        'expired_days' => $quota ? $quota->expired_days : 0,
        'anomaly' => false,
        'notes' => [],
    ];
}

foreach ($rows as &$row) {
    if ($row['period_year'] !== '-') {
        $calculatedTotal = floatval($row['expected_total']) + floatval($row['carry_over']);

        if (floatval($row['quota_total']) !== $calculatedTotal) {
            $row['anomaly'] = true;
            $row['notes'][] = 'Total quota tidak sama dengan expected quota + carry over.';
        }

        if (floatval($row['carry_over']) > 10) {
            $row['anomaly'] = true;
            $row['notes'][] = 'Carry over lebih dari 10 hari.';
        }

        if (floatval($row['used']) !== floatval($row['approved_used'])) {
            $row['anomaly'] = true;
            $row['notes'][] = 'Used quota tidak sinkron dengan approved leave history.';
        }

        if (floatval($row['remaining']) !== max(0, floatval($row['quota_total']) - floatval($row['approved_used']))) {
            $row['anomaly'] = true;
            $row['notes'][] = 'Remaining quota tidak sesuai total - used.';
        }
    }
}
unset($row);

function printRow($row)
{
    echo sprintf(
        "%s | %s | join=%s | period=%s..%s | expect=%s | total=%s | used=%s | approved=%s | rem=%s | carry=%s | carry_year=%s | expired=%s%s\n",
        str_pad($row['user'], 25),
        str_pad($row['role'], 8),
        $row['join_date'],
        $row['period_start'],
        $row['period_end'],
        $row['expected_total'],
        $row['quota_total'],
        $row['used'],
        $row['approved_used'],
        $row['remaining'],
        $row['carry_over'],
        $row['carry_from_year'],
        $row['expired_days'],
        $row['anomaly'] ? ' | ANOMALY: ' . implode('; ', $row['notes']) : ''
    );
}

foreach ($rows as $row) {
    printRow($row);
}

$anomalyCount = count(array_filter($rows, fn($row) => $row['anomaly']));
echo "\nSummary: " . count($rows) . " users checked, " . $anomalyCount . " anomalies found.\n";
