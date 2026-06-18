<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LeaveType;
use App\Models\LeaveQuota;
use App\Models\User;

$leaveType = LeaveType::find(1);
if (!$leaveType) {
    echo "LeaveType id=1 not found\n";
    exit(1);
}

echo "LeaveType 1: ".json_encode($leaveType->toArray())."\n";
$quotas = LeaveQuota::where('leave_type_id', 1)->with('user')->get();
echo "LeaveQuotas count " . count($quotas) . "\n";
foreach ($quotas as $i => $quota) {
    if ($i >= 20) break;
    echo sprintf(
        "%d user=%s join=%s year=%s total=%s used=%s rem=%s carry=%s exp=%s\n",
        $quota->id,
        $quota->user->full_name,
        $quota->user->join_date,
        $quota->year,
        $quota->total_quota,
        $quota->used_quota,
        $quota->remaining_quota,
        $quota->carried_over_from_year,
        $quota->expired_days
    );
}
