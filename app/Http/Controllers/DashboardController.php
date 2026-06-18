<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveQuotaService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Arahkan ke dashboard sesuai role
        if ($user->isHrd())     return $this->hrdDashboard();
        if ($user->isAtasan())  return $this->atasanDashboard();
        return $this->karyawanDashboard();
    }

    private function hrdDashboard()
    {
        $supervisorsOnLeave = User::where('role', 'atasan')
                                   ->where('status_aktif', 'cuti')
                                   ->count();

        $hrPendingApprovals = LeaveRequest::where('status', 'menunggu')
            ->whereHas('user.supervisor', fn($q) =>
                $q->where('status_aktif', 'cuti')
            )
            ->count();

        $stats = [
            'total_karyawan'      => User::where('role', 'karyawan')->count(),
            'total_atasan'        => User::where('role', 'atasan')->count(),
            'cuti_menunggu'       => LeaveRequest::where('status', 'menunggu')->count(),
            'cuti_disetujui'      => LeaveRequest::where('status', 'disetujui')
                                            ->whereYear('request_date', now()->year)->count(),
            'supervisors_on_leave' => $supervisorsOnLeave,
            'hr_pending_approvals' => $hrPendingApprovals,
        ];

        $recentRequests = LeaveRequest::with(['user', 'leaveType'])->latest()->take(10)->get();

        return view('dashboard.hrd', compact('stats', 'recentRequests'));
    }

    private function atasanDashboard()
    {
        $user = Auth::user();
        $quotaService = app(LeaveQuotaService::class);

        $pendingRequests = LeaveRequest::with(['user', 'leaveType'])
            ->whereHas('user', fn($q) => $q->where('supervisor_id', $user->id))
            ->where('status', 'menunggu')
            ->latest()
            ->get();

        $annualLeaveData = $this->getActiveAnnualLeaveData($user);

        $myQuotas = $user->leaveQuotas()
                         ->with('leaveType');

        if ($annualLeaveData['quota_year'] !== null) {
            $myQuotas->where('year', $annualLeaveData['quota_year']);
        }

        $myQuotas = $myQuotas->get();

        $stats = [
            'menunggu'             => $pendingRequests->count(),
            'disetujui'            => LeaveRequest::whereHas('user', fn($q) => $q->where('supervisor_id', $user->id))
                                                ->where('status', 'disetujui')
                                                ->whereYear('request_date', now()->year)->count(),
            'total_bawahan'        => $user->subordinates()->count(),
            'sisa_cuti_tahunan'    => $annualLeaveData['remaining_quota'],
            'quota_year'           => $annualLeaveData['quota_year'],
        ];

        return view('dashboard.atasan', compact('stats', 'pendingRequests', 'myQuotas', 'annualLeaveData'));
    }

    private function karyawanDashboard()
    {
        $user = Auth::user();

        $myRequests = LeaveRequest::with('leaveType')
                                  ->where('user_id', $user->id)
                                  ->latest()->take(5)->get();

        $annualLeaveData = $this->getActiveAnnualLeaveData($user);

        $myQuotas = $user->leaveQuotas()
                         ->with('leaveType');

        if ($annualLeaveData['quota_year'] !== null) {
            $myQuotas->where('year', $annualLeaveData['quota_year']);
        }

        $myQuotas = $myQuotas->get();

        $stats = [
            'total_pengajuan' => LeaveRequest::where('user_id', $user->id)->count(),
            'menunggu'        => LeaveRequest::where('user_id', $user->id)->where('status', 'menunggu')->count(),
            'disetujui'       => LeaveRequest::where('user_id', $user->id)->where('status', 'disetujui')
                                             ->whereYear('request_date', now()->year)->count(),
        ];

        return view('dashboard.karyawan', compact('stats', 'myRequests', 'myQuotas', 'annualLeaveData'));
    }

    private function getActiveAnnualLeaveData($user): array
    {
        $quotaService = app(LeaveQuotaService::class);
        $annualLeaveType = LeaveType::where('code', 'CT')->first();

        $data = [
            'quota_year' => null,
            'total_quota' => 0,
            'used_quota' => 0,
            'remaining_quota' => 0,
            'carry_over' => 0,
            'expired_days' => 0,
            'next_anniversary' => null,
            'days_until_expiry' => null,
            'expiry_warning' => null,
            'is_entitled' => $quotaService->isEntitledToAnnualLeave($user),
        ];

        if (!$annualLeaveType) {
            return $data;
        }

        $periodYear = $quotaService->getQuotaPeriodYear($user);
        $data['quota_year'] = $periodYear;

        if ($periodYear === null) {
            return $data;
        }

        $quota = $user->leaveQuotas()
            ->where('leave_type_id', $annualLeaveType->id)
            ->where('year', $periodYear)
            ->first();

        if (!$quota) {
            $quota = $quotaService->getOrCreateQuota($user, $annualLeaveType, $periodYear);
        }

        $data['total_quota'] = $quota->total_quota;
        $data['used_quota'] = $quota->used_quota;
        $data['remaining_quota'] = $quota->remaining_quota;
        $data['carry_over'] = max(0, $quota->total_quota - $quotaService->calculateAnnualLeaveQuota($user));
        $data['expired_days'] = $quota->expired_days;
        $data['next_anniversary'] = $quotaService->getNextAnniversary($user);
        $data['days_until_expiry'] = $quotaService->getDaysUntilNextAnniversary($user);
        $data['expiry_warning'] = $quotaService->getAnnualLeaveExpiryInfo($user);

        return $data;
    }
}