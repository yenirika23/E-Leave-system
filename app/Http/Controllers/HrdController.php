<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveReportExport;
use App\Exports\AnnualLeaveQuotaExport;
use App\Services\LeaveQuotaService;
use Illuminate\Support\Facades\Auth;

class HrdController extends Controller
{
    protected LeaveQuotaService $quotaService;

    public function __construct(LeaveQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }
    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */

    // Daftar semua karyawan
    public function userIndex()
    {
        $users = User::with(['department', 'supervisor'])
                     ->orderBy('role')
                     ->orderBy('full_name')
                     ->paginate(15);

        return view('hrd.users.index', compact('users'));
    }

    // Form tambah karyawan
    public function userCreate()
    {
        $departments = Department::all();
        $atasan      = User::where('role', 'atasan')
                            ->where('is_active', true)
                            ->get();

        return view('hrd.users.create', compact('departments', 'atasan'));
    }

    // Simpan karyawan baru
    public function userStore(Request $request)
    {
        $request->validate([
            'full_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'gender'        => 'required|in:L,P',
            'position'      => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'role'          => 'required|in:hrd,atasan,karyawan',
            'supervisor_id' => 'nullable|exists:users,id',
            'status_aktif'  => 'nullable|in:aktif,cuti',
            'join_date'     => 'required|date',
        ]);

        $nik = User::generateNikFromJoinDate($request->join_date);

        // Password default = NIK
        $user = User::create([
            ...$request->only([
                'full_name',
                'email',
                'gender',
                'birth_date',
                'phone',
                'position',
                'department_id',
                'role',
                'supervisor_id',
                'join_date',
                'status_aktif',
            ]),

            'nik'                  => $nik,
            'password'             => Hash::make($nik),
            'must_change_password' => true,
            'is_active'            => true,
        ]);

        $quotaService = app(LeaveQuotaService::class);

        $openingBalance = floatval($request->input('opening_balance', 0));

        // Buat quota cuti otomatis
        foreach (LeaveType::where('is_active', true)->get() as $leaveType) {
            if ($leaveType->code === 'CT' && $request->join_date) {
                $periodYear = $quotaService->getQuotaPeriodYear($user);
                $quota = $quotaService->getOrCreateQuota($user, $leaveType, $periodYear);
                $carryData = $quotaService->getPreviousPeriodCarryOver($user, $leaveType);
                $quotaValue = $quotaService->calculateAnnualLeaveQuota($user) + $carryData['carry'] + $openingBalance;
                $quotaValue = min($quotaValue, 12 + 10 + 11);

                // Validasi bahwa annual leave tidak akan disimpan dengan quota 0
                $quotaService->validateAnnualLeaveQuota($user, $leaveType, $quotaValue);

                $quota->fill([
                    'total_quota' => $quotaValue,
                    'used_quota' => 0,
                    'remaining_quota' => $quotaValue,
                    'is_automatic' => true,
                    'carried_over_from_year' => $carryData['carry'] > 0 ? $carryData['from_year'] : null,
                    'opening_balance' => $openingBalance,
                ]);
                $quota->save();
            } else {
                $quotaValue = $leaveType->is_unlimited
                    ? 9999
                    : $leaveType->default_quota;

                LeaveQuota::create([
                    'user_id'         => $user->id,
                    'leave_type_id'   => $leaveType->id,
                    'year'            => now()->year,
                    'total_quota'     => $quotaValue,
                    'used_quota'      => 0,
                    'remaining_quota' => $quotaValue,
                    'opening_balance' => 0,
                ]);
            }
        }

        return redirect()->route('hrd.users.index')
            ->with(
                'success',
                'Karyawan ' . $user->full_name .
                ' berhasil ditambahkan! NIK: ' . $nik . ' dan password default telah diset ke NIK tersebut.'
            );
    }

    // Form edit karyawan
    public function userEdit(User $user)
    {
        $departments = Department::all();

        $atasan = User::where('role', 'atasan')
            ->where('is_active', true)
            ->get();

        return view('hrd.users.edit', compact(
            'user',
            'departments',
            'atasan'
        ));
    }

    // Simpan perubahan data karyawan
    public function userUpdate(Request $request, User $user)
    {
        $request->validate([
            'nik'         => 'required|unique:users,nik,' . $user->id . '|max:20',
            'full_name'   => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'gender'      => 'required|in:L,P',
            'role'        => 'required|in:hrd,atasan,karyawan',
            'status_aktif' => 'nullable|in:aktif,cuti',
        ]);

        $user->update($request->only([
            'nik',
            'full_name',
            'email',
            'gender',
            'birth_date',
            'phone',
            'position',
            'department_id',
            'role',
            'supervisor_id',
            'join_date',
            'status_aktif',
            'is_active',
        ]));

        return redirect()->route('hrd.users.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    // Reset password ke NIK
    public function resetPassword(User $user)
    {
        $user->update([
            'password'             => Hash::make($user->nik),
            'must_change_password' => true,
        ]);

        return back()->with(
            'success',
            'Password ' . $user->full_name .
            ' berhasil direset ke NIK (' . $user->nik . ').'
        );
    }

    // Aktif / nonaktif akun
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active
            ? 'diaktifkan'
            : 'dinonaktifkan';

        return back()->with(
            'success',
            'Akun ' . $user->full_name .
            ' berhasil ' . $status . '.'
        );
    }

    // Hapus user
    public function userDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'Tidak bisa menghapus akun sendiri.'
            );
        }

        $name = $user->full_name;

        $user->delete();

        return redirect()->route('hrd.users.index')
            ->with(
                'success',
                'Data karyawan ' . $name . ' berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE TYPES
    |--------------------------------------------------------------------------
    */

    // Simpan jenis cuti baru
    public function leaveTypeStore(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:10|unique:leave_types,code',
            'default_quota'  => 'required_unless:is_unlimited,1|nullable|integer|min:0|max:365',
            'unit'           => 'required|string',
            'description'    => 'nullable|string|max:300',
            'is_unlimited'   => 'nullable|boolean',
            'allow_half_day' => 'nullable|boolean',
        ]);

        $isUnlimited = $request->boolean('is_unlimited');

        $leaveType = LeaveType::create([
            'name'           => $request->name,
            'code'           => strtoupper($request->code),
            'default_quota'  => $isUnlimited ? 0 : $request->default_quota,
            'unit'           => $request->unit,
            'description'    => $request->description,
            'is_active'      => true,
            'is_unlimited'   => $isUnlimited,
            'allow_half_day' => $request->boolean('allow_half_day'),
        ]);

        // Buat quota otomatis
        $quotaValue = $isUnlimited
            ? 9999
            : $leaveType->default_quota;

        foreach (User::where('is_active', true)->get() as $u) {

            LeaveQuota::firstOrCreate(
                [
                    'user_id'       => $u->id,
                    'leave_type_id' => $leaveType->id,
                    'year'          => now()->year,
                ],
                [
                    'total_quota'     => $quotaValue,
                    'used_quota'      => 0,
                    'remaining_quota' => $quotaValue,
                ]
            );
        }

        return redirect()->route('hrd.leave-types.index')
            ->with(
                'success',
                'Jenis cuti ' . $leaveType->name . ' berhasil ditambahkan!'
            );
    }

    // Update jenis cuti
    public function leaveTypeUpdate(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:10|unique:leave_types,code,' . $leaveType->id,
            'default_quota'  => 'nullable|integer|min:0|max:365',
            'unit'           => 'required|string',
            'description'    => 'nullable|string|max:300',
            'is_active'      => 'nullable|boolean',
            'is_unlimited'   => 'nullable|boolean',
            'allow_half_day' => 'nullable|boolean',
        ]);

        $leaveType->update([
            'name'           => $request->name,
            'code'           => strtoupper($request->code),
            'default_quota'  => $request->boolean('is_unlimited')
                                    ? 0
                                    : ($request->default_quota ?? 0),
            'unit'           => $request->unit,
            'description'    => $request->description,
            'is_active'      => $request->boolean('is_active'),
            'is_unlimited'   => $request->boolean('is_unlimited'),
            'allow_half_day' => $request->boolean('allow_half_day'),
        ]);

        return redirect()->route('hrd.leave-types.index')
            ->with('success', 'Jenis cuti berhasil diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE REPORT
    |--------------------------------------------------------------------------
    */

    // Halaman laporan
    public function leaveReport(Request $request)
    {
        $query = LeaveRequest::with([
            'user.department',
            'leaveType',
            'approver'
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->department_id) {
            $query->whereHas('user', fn($q) =>
                $q->where('department_id', $request->department_id)
            );
        }

        if ($request->year) {
            $query->whereYear('request_date', $request->year);
        }

        if ($request->month) {
            $query->whereMonth('request_date', $request->month);
        }

        if ($request->leave_type_id) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $year = $request->year ?? now()->year;

        // Statistik
        $allData = (clone $query)->get();

        $stats = [
            'total'      => $allData->count(),
            'menunggu'   => $allData->where('status', 'menunggu')->count(),
            'disetujui'  => $allData->where('status', 'disetujui')->count(),
            'ditolak'    => $allData->where('status', 'ditolak')->count(),
            'dibatalkan' => $allData->where('status', 'dibatalkan')->count(),
            'total_hari' => $allData->where('status', 'disetujui')->sum('total_days'),
        ];

        $monthStats = LeaveRequest::selectRaw('MONTH(request_date) as month, status, count(*) as total')
            ->whereYear('request_date', $year)
            ->groupBy('month', 'status')
            ->get()
            ->groupBy('month');

        $chartLabels = collect(range(1, 12))
            ->map(fn($m) => \Carbon\Carbon::create()->month($m)->locale('id')->monthName)
            ->all();

        $chartMonthly = collect(range(1, 12))->map(function ($month) use ($monthStats) {
            $data = $monthStats->get($month) ?? collect();
            return [
                'month'     => $month,
                'menunggu'  => $data->where('status', 'menunggu')->sum('total'),
                'disetujui' => $data->where('status', 'disetujui')->sum('total'),
                'ditolak'   => $data->where('status', 'ditolak')->sum('total'),
            ];
        })->all();

        $chartStatus = [
            'disetujui' => $allData->where('status', 'disetujui')->count(),
            'ditolak'   => $allData->where('status', 'ditolak')->count(),
            'menunggu'  => $allData->where('status', 'menunggu')->count(),
        ];

        $chartDepartmentData = (clone $query)
            ->where('status', 'disetujui')
            ->with('user.department')
            ->get()
            ->groupBy(fn($item) => $item->user->department?->name ?? 'Tanpa Departemen')
            ->map(fn($items) => $items->sum('total_days'))
            ->sortDesc();

        $leaveRequests = $query->latest()
                                ->paginate(20)
                                ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $leaveTypes  = LeaveType::orderBy('name')->get();

        return view('hrd.leave-report', compact(
            'leaveRequests',
            'departments',
            'leaveTypes',
            'stats',
            'chartLabels',
            'chartMonthly',
            'chartStatus',
            'chartDepartmentData'
        ));
    }

    // Halaman approval HR ketika atasan cuti
    public function approvalList(Request $request)
    {
        $query = LeaveRequest::with(['user.department', 'leaveType', 'user.supervisor'])
            ->where('status', 'menunggu')
            ->where(function($q) {
                // Pengajuan yang dialihkan karena atasan sedang cuti
                $q->whereHas('user.supervisor', fn($q2) =>
                    $q2->where('status_aktif', 'cuti')
                )
                // atau pengajuan yang dibuat oleh atasan itu sendiri
                ->orWhereHas('user', fn($q3) =>
                    $q3->where('role', 'atasan')
                );
            });

        if ($request->department_id) {
            $query->whereHas('user', fn($q) =>
                $q->where('department_id', $request->department_id)
            );
        }

        if ($request->keyword) {
            $keyword = '%' . $request->keyword . '%';
            $query->whereHas('user', fn($q) =>
                $q->where('full_name', 'like', $keyword)
                  ->orWhere('nik', 'like', $keyword)
            );
        }

        $requests = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $totalRequests = $requests->total();

        return view('hrd.leave-approval-list', compact('requests', 'departments', 'totalRequests'));
    }

    public function approveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'action'           => 'required|in:disetujui,ditolak',
            'rejection_reason' => 'required_if:action,ditolak|nullable|string',
        ]);

        $user = Auth::user();

        if ($leaveRequest->status !== 'menunggu') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // HR boleh memproses jika pengajuan dialihkan karena atasan cuti,
        // atau pengajuan dibuat oleh atasan itu sendiri
        if (! (
            $leaveRequest->user->supervisor?->isOnCuti() ||
            $leaveRequest->user->role === 'atasan'
        )) {
            abort(403);
        }

        if ($request->action === 'disetujui') {
            $leaveType = $leaveRequest->leaveType;

            if (!$leaveType->is_unlimited) {
                $approved = $this->quotaService->deductQuotaOnApproval(
                    $leaveRequest->user,
                    $leaveType,
                    $leaveRequest->day_type,
                    $leaveRequest->total_days,
                    $leaveRequest->start_date
                );

                if (!$approved) {
                    return back()->with(
                        'error',
                        'Kuota tidak mencukupi untuk menyetujui cuti ini.'
                    );
                }
            }
        }

        $leaveRequest->update([
            'status'          => $request->action,
            'disetujui_oleh'  => 'hr',
            'id_approver'     => $user->id,
            'approved_by'     => $user->id,
            'approved_at'     => now(),
            'rejection_reason' => $request->action === 'ditolak'
                ? $request->rejection_reason
                : null,
        ]);

        Notification::create([
            'user_id'          => $leaveRequest->user_id,
            'title'            => 'Status Cuti Diperbarui',
            'message'          => 'Pengajuan cuti Anda ' .
                                  ($request->action === 'disetujui' ? 'disetujui ✅' : 'ditolak ❌') .
                                  ' oleh ' . $user->full_name . '.',
            'type'             => 'info',
            'leave_request_id' => $leaveRequest->id,
        ]);

        return back()->with('success', 'Pengajuan cuti berhasil diproses oleh HR.');
    }

    // Export Excel
    public function exportExcel(Request $request)
    {
        $filters = $request->only([
            'status',
            'department_id',
            'year',
            'month',
            'leave_type_id'
        ]);

        $filename = 'laporan-cuti-' .
                    now()->format('Ymd-His') .
                    '.xlsx';

        return Excel::download(
            new LeaveReportExport($filters),
            $filename
        );
    }

    // Export PDF
    public function exportPdf(Request $request)
    {
        $query = LeaveRequest::with([
            'user.department',
            'leaveType',
            'approver'
        ])->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->department_id) {
            $query->whereHas('user', fn($q) =>
                $q->where('department_id', $request->department_id)
            );
        }

        if ($request->year) {
            $query->whereYear('request_date', $request->year);
        }

        if ($request->month) {
            $query->whereMonth('request_date', $request->month);
        }

        if ($request->leave_type_id) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $leaveRequests = $query->get();

        $stats = [
            'total'      => $leaveRequests->count(),
            'disetujui'  => $leaveRequests->where('status', 'disetujui')->count(),
            'ditolak'    => $leaveRequests->where('status', 'ditolak')->count(),
            'menunggu'   => $leaveRequests->where('status', 'menunggu')->count(),
            'total_hari' => $leaveRequests->where('status', 'disetujui')->sum('total_days'),
        ];

        $filterInfo = [
            'status'     => $request->status
                                ? ucfirst($request->status)
                                : 'Semua',

            'bulan'      => $request->month
                                ? \Carbon\Carbon::create()
                                    ->month($request->month)
                                    ->locale('id')
                                    ->monthName
                                : 'Semua',

            'tahun'      => $request->year ?? 'Semua',

            'digenerate' => now()
                                ->locale('id')
                                ->isoFormat('dddd, D MMMM Y — HH:mm'),
        ];

        $pdf = Pdf::loadView(
                    'hrd.leave-report-pdf',
                    compact('leaveRequests', 'stats', 'filterInfo')
                )
                ->setPaper('a4', 'landscape')
                ->setOption('defaultFont', 'sans-serif');

        $filename = 'laporan-cuti-' .
                    now()->format('Ymd') .
                    '.pdf';

        return $pdf->download($filename);
    }

    public function annualLeaveQuotaReport(Request $request)
    {
        $year = $request->input('year', now()->year);

        $leaveType = LeaveType::where('code', 'CT')->first();

        $query = LeaveQuota::with(['user.department', 'leaveType'])
            ->when($leaveType, fn($q) => $q->where('leave_type_id', $leaveType->id))
            ->where('year', $year)
            ->when($request->department_id, fn($q) =>
                $q->whereHas('user', fn($q) =>
                    $q->where('department_id', $request->department_id)
                )
            );

        $quotas = $query->orderBy('year')
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        $stats = [
            'total_records'    => $quotas->total(),
            'total_quota'      => $quotas->sum('total_quota'),
            'used_quota'       => $quotas->sum('used_quota'),
            'remaining_quota'  => $quotas->sum('remaining_quota'),
            'carried_over'     => $quotas->sum('carried_over_from_year'),
            'expired_days'     => $quotas->sum('expired_days'),
        ];

        return view('hrd.annual-leave-quota-report', compact(
            'quotas',
            'departments',
            'year',
            'stats'
        ));
    }

    public function exportAnnualLeaveQuotaExcel(Request $request)
    {
        $filters = $request->only([
            'year',
            'department_id'
        ]);

        $filename = 'laporan-cuti-tahunan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new AnnualLeaveQuotaExport($filters),
            $filename
        );
    }

    // Detail pengajuan cuti untuk HRD
    public function showLeaveRequest(LeaveRequest $leaveRequest)
    {
        return view('hrd.leave-request-detail', compact('leaveRequest'));
    }
}