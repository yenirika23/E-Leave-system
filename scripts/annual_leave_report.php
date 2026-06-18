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
if (!$leaveType) {
    echo "Leave type CT not found.\n";
    exit(1);
}

$users = User::whereNotNull('join_date')->orderBy('join_date')->get();

$rows = [];
foreach ($users as $user) {
    $periodYear = $service->getQuotaPeriodYear($user);
    $periodStart = $service->getQuotaPeriodStartDate($user);
    $periodEnd = $service->getQuotaPeriodEndDate($user);
    $expected = $service->calculateAnnualLeaveQuota($user);

    $quota = $user->leaveQuotas()
        ->where('leave_type_id', $leaveType->id)
        ->when($periodYear !== null, fn($q) => $q->where('year', $periodYear))
        ->first();

    $approvedUsed = LeaveRequest::where('user_id', $user->id)
        ->where('leave_type_id', $leaveType->id)
        ->where('status', 'disetujui')
        ->when($periodStart && $periodEnd, fn($q) => $q->whereBetween('start_date', [$periodStart->toDateString(), $periodEnd->subDay()->toDateString()]))
        ->sum('total_days');

    $carryAmount = $quota ? max(0, $quota->total_quota - $expected) : 0;
    if ($carryAmount > 10) {
        $carryAmount = 10;
    }

    $rows[] = [
        'name' => $user->full_name,
        'role' => $user->role,
        'join' => $user->join_date->toDateString(),
        'period' => $periodStart ? $periodStart->toDateString() . ' - ' . $periodEnd->subDay()->toDateString() : '-',
        'expected' => $expected,
        'total' => $quota ? $quota->total_quota : 0,
        'used' => $quota ? $quota->used_quota : 0,
        'remaining' => $quota ? $quota->remaining_quota : 0,
        'approved' => $approvedUsed,
        'carry' => $carryAmount,
        'expired' => $quota ? $quota->expired_days : 0,
    ];
}

$fmt = "%-20s | %-8s | %-10s | %-23s | %5s | %5s | %5s | %9s | %8s | %5s | %7s\n";
echo sprintf($fmt, 'Name', 'Role', 'Join', 'Period', 'Exp', 'Total', 'Used', 'Rem', 'Approved', 'Carry', 'Expired');
echo str_repeat('-', 115) . "\n";
foreach ($rows as $row) {
    echo sprintf(
        $fmt,
        $row['name'],
        $row['role'],
        $row['join'],
        $row['period'],
        $row['expected'],
        $row['total'],
        $row['used'],
        $row['remaining'],
        $row['approved'],
        $row['carry'],
        $row['expired']
    );
}
