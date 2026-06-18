<?php

namespace App\Http\Controllers;

use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\User;
use App\Services\LeaveQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    protected LeaveQuotaService $quotaService;

    public function __construct(LeaveQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /*
    |--------------------------------------------------------------------------
    | FORM PENGAJUAN CUTI
    |--------------------------------------------------------------------------
    */

    // Tampilkan form pengajuan cuti
    public function create()
    {
        $leaveTypes = LeaveType::where('is_active', true)->get();

        return view('leave.create', compact('leaveTypes'));
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PENGAJUAN CUTI
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date|after_or_equal:today',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'day_type'      => 'required|in:full,morning,afternoon',
            'reason'        => 'required|string|min:10',
            'notes'         => 'nullable|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $user      = Auth::user();
        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        /*
        |--------------------------------------------------------------------------
        | HITUNG TOTAL HARI
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse($request->start_date);
        $endDate   = Carbon::parse($request->end_date);
        $dayType   = $request->day_type;

        // Half day
        if ($dayType !== 'full') {

            // Half day hanya boleh 1 hari
            $endDate = $startDate->copy();

            // Simpan 0.5 hari
            $totalDays = 0.5;

        } else {

            // Full day
            $totalDays = $this->calculateWorkingDays(
                $startDate,
                $endDate
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI HALF DAY
        |--------------------------------------------------------------------------
        */

        if (
            $dayType !== 'full' &&
            !$leaveType->allow_half_day
        ) {
            return back()
                ->withErrors([
                    'day_type' => 'Jenis cuti ini tidak mendukung setengah hari.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI KOMBINASI UPL
        |--------------------------------------------------------------------------
        */

        // UPL Full Day: hanya boleh full day
        if ($leaveType->code === 'UPLF') {
            if ($dayType !== 'full') {
                return back()
                    ->withErrors(['day_type' => 'UPL Full Day hanya bisa dipilih Full Day.'])
                    ->withInput();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI KUOTA
        |--------------------------------------------------------------------------
        */

        // Skip quota validation for annual leave sebelum genap 1 tahun
        if (!$leaveType->is_unlimited) {
            $needDays = $totalDays;
            $skipQuotaValidation = $leaveType->code === 'CT' &&
                !$this->quotaService->isEntitledToAnnualLeave($user);

            $quotaYear = $this->quotaService->getQuotaPeriodYear($user) ?? now()->year;
            $quota = $this->quotaService->getOrCreateQuota($user, $leaveType, $quotaYear);

            if ($leaveType->code !== 'CT' && $quota->total_quota === 0) {
                $quota->total_quota = $leaveType->default_quota;
                $quota->remaining_quota = $leaveType->default_quota;
                $quota->save();
            }

            if ($leaveType->code === 'CT' &&
                $this->quotaService->isEntitledToAnnualLeave($user) &&
                $quota->total_quota === 0
            ) {
                $carryData = $this->quotaService->getPreviousPeriodCarryOver($user, $leaveType);
                $expectedTotal = $this->quotaService->calculateAnnualLeaveQuota($user) + $carryData['carry'];

                $quota->total_quota = $expectedTotal;
                $quota->remaining_quota = $expectedTotal;
                $quota->is_automatic = true;
                $quota->save();
            }

            if (!$skipQuotaValidation) {
                if (
                    !$quota ||
                    $quota->remaining_quota < $needDays
                ) {
                    return back()
                        ->withErrors([
                            'quota' =>
                                'Kuota cuti tidak mencukupi. ' .
                                'Sisa: ' .
                                ($quota->remaining_quota ?? 0) .
                                ' ' .
                                ($leaveType->unit ?? 'hari') .
                                '.'
                        ])
                        ->withInput();
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPLOAD DOKUMEN
        |--------------------------------------------------------------------------
        */

        $documentPath = null;
        $documentName = null;

        if ($request->hasFile('document')) {

            $file = $request->file('document');

            $documentName = $file->getClientOriginalName();

            $documentPath = $file->store(
                'leave-documents',
                'public'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI CUTI SAKIT
        |--------------------------------------------------------------------------
        */

        // CS = Cuti Sakit
        if (
            $leaveType->code === 'CS' &&
            !$documentPath
        ) {
            return back()
                ->withErrors([
                    'document' =>
                        'Cuti Sakit wajib melampirkan surat dokter.'
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN PENGAJUAN
        |--------------------------------------------------------------------------
        */

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $user->id,
            'leave_type_id' => $leaveType->id,
            'request_date'  => now()->toDateString(),

            'start_date'    => $startDate->toDateString(),
            'end_date'      => $endDate->toDateString(),

            'total_days'    => $totalDays,
            'day_type'      => $dayType,

            'reason'        => $request->reason,
            'notes'         => $request->notes,

            'document_path' => $documentPath,
            'document_name' => $documentName,

            'status'        => 'menunggu',
        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFIKASI KE ATASAN
        |--------------------------------------------------------------------------
        */

        // Jika pengaju adalah atasan, langsung diberitahukan ke HR untuk diproses oleh HR
        if ($user->isAtasan()) {
            User::where('role', 'hrd')
                ->where('is_active', true)
                ->get()
                ->each(function (User $hr) use ($user, $leaveType, $leaveRequest) {
                    Notification::create([
                        'user_id'          => $hr->id,
                        'title'            => 'Pengajuan Cuti dari Atasan',
                        'message'          => $user->full_name .
                                             ' (Atasan) mengajukan ' .
                                             $leaveType->name .
                                             ' selama ' .
                                             $leaveRequest->getTotalLabel() .
                                             '.',
                        'type'             => 'info',
                        'leave_request_id' => $leaveRequest->id,
                    ]);
                });

            $successMessage = 'Pengajuan cuti berhasil dikirim! Pengajuan atasan akan diproses oleh HR.';

        // Jika atasan user-nya sedang cuti, juga notify HR (existing flow)
        } elseif ($user->supervisor && $user->supervisor->isOnCuti()) {
            User::where('role', 'hrd')
                ->where('is_active', true)
                ->get()
                ->each(function (User $hr) use ($user, $leaveType, $leaveRequest) {
                    Notification::create([
                        'user_id'          => $hr->id,
                        'title'            => 'Pengajuan Cuti Baru dari Tim Anda',
                        'message'          => $user->full_name .
                                             ' mengajukan ' .
                                             $leaveType->name .
                                             ' selama ' .
                                             $leaveRequest->getTotalLabel() .
                                             '. Atasannya sedang cuti.',
                        'type'             => 'info',
                        'leave_request_id' => $leaveRequest->id,
                    ]);
                });

            $successMessage = 'Pengajuan cuti berhasil dikirim! Atasan saat ini sedang cuti, pengajuan akan diproses oleh HR.';

        // Normal: notify atasan langsung
        } elseif ($user->supervisor_id) {
            Notification::create([
                'user_id'          => $user->supervisor_id,
                'title'            => 'Pengajuan Cuti Baru',
                'message'          => $user->full_name .
                                      ' mengajukan ' .
                                      $leaveType->name .
                                      ' selama ' .
                                      $leaveRequest->getTotalLabel() .
                                      '.',
                'type'             => 'info',
                'leave_request_id' => $leaveRequest->id,
            ]);

            $successMessage = 'Pengajuan cuti berhasil dikirim! Menunggu persetujuan atasan.';

        } else {
            $successMessage = 'Pengajuan cuti berhasil dikirim!';
        }

        return redirect()
            ->route('leave.my-requests')
            ->with('success', $successMessage);
    }

    /*
    |--------------------------------------------------------------------------
    | RIWAYAT CUTI KARYAWAN
    |--------------------------------------------------------------------------
    */

    public function myRequests()
    {
        $requests = LeaveRequest::with('leaveType')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('leave.my-requests', compact('requests'));
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL PENGAJUAN CUTI
    |--------------------------------------------------------------------------
    */

    public function show(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        // Karyawan hanya boleh melihat miliknya sendiri
        if (
            $user->isKaryawan() &&
            $leaveRequest->user_id !== $user->id
        ) {
            abort(403);
        }

        return view('leave.show', compact('leaveRequest'));
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL PENGAJUAN CUTI UNTUK ATASAN
    |--------------------------------------------------------------------------
    */

    public function approvalShow(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        // Pastikan atasan yang benar
        if ($leaveRequest->user->supervisor_id !== $user->id) {
            abort(403);
        }

        if ($user->isOnCuti()) {
            abort(403);
        }

        return view('leave.approval-show', compact('leaveRequest'));
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR APPROVAL ATASAN
    |--------------------------------------------------------------------------
    */

    public function approvalList()
    {
        $user = Auth::user();

        if ($user->isOnCuti()) {
            $requests = collect();
            return view('leave.approval-list', compact('requests'));
        }

        $query = LeaveRequest::with([
                'user',
                'leaveType'
            ])
            ->whereHas('user', function ($query) {
                $query->where(
                    'supervisor_id',
                    Auth::id()
                );
            });

        // Filter berdasarkan status jika ada
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $requests = $query->latest()->paginate(10);

        return view('leave.approval-list', compact('requests'));
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE / REJECT CUTI
    |--------------------------------------------------------------------------
    */

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'action'           => 'required|in:disetujui,ditolak',
            'rejection_reason' => 'required_if:action,ditolak|nullable|string',
        ]);

        $user = Auth::user();

        // Pastikan atasan yang benar
        if ($leaveRequest->user->supervisor_id !== $user->id) {
            abort(403);
        }

        if ($user->isOnCuti()) {
            abort(403);
        }

        // Pastikan masih pending
        if (!$leaveRequest->isPending()) {

            return back()->with(
                'error',
                'Pengajuan ini sudah diproses sebelumnya.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | KURANGI KUOTA SEBELUM UPDATE STATUS
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $leaveRequest->update([
            'status'           => $request->action,
            'approved_by'      => $user->id,
            'approved_at'      => now(),

            'rejection_reason' =>
                $request->action === 'ditolak'
                    ? $request->rejection_reason
                    : null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFIKASI KE KARYAWAN
        |--------------------------------------------------------------------------
        */

        $statusText = $request->action === 'disetujui'
            ? 'disetujui ✅'
            : 'ditolak ❌';

        Notification::create([
            'user_id'          => $leaveRequest->user_id,

            'title'            => 'Status Cuti Diperbarui',

            'message'          =>
                'Pengajuan cuti Anda ' .
                $statusText .
                ' oleh ' .
                $user->full_name .
                '.',

            'type'             =>
                $request->action === 'disetujui'
                    ? 'success'
                    : 'warning',

            'leave_request_id' => $leaveRequest->id,
        ]);

        return redirect()
            ->route('leave.approval-list')
            ->with(
                'success',
                'Pengajuan cuti berhasil ' .
                $request->action .
                '.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | BATALKAN PENGAJUAN CUTI
    |--------------------------------------------------------------------------
    */

    public function cancel(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        // Pastikan milik user login
        if ($leaveRequest->user_id !== $user->id) {
            abort(403, 'Ini bukan pengajuan cuti Anda.');
        }

        // Hanya status menunggu yang boleh dibatalkan
        if (!$leaveRequest->isPending()) {
            return back()->with(
                'error',
                'Hanya pengajuan berstatus Menunggu yang bisa dibatalkan.'
            );
        }

        $leaveRequest->update([
            'status' => 'dibatalkan'
        ]);

        return redirect()
            ->route('leave.my-requests')
            ->with('success', 'Pengajuan cuti berhasil dibatalkan.');
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUS PENGAJUAN CUTI
    |--------------------------------------------------------------------------
    */

    public function destroy(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        // Pastikan milik user login
        if ($leaveRequest->user_id !== $user->id) {
            abort(403);
        }

        // Hanya yang dibatalkan
        if (!$leaveRequest->isCancelled()) {

            return back()->with(
                'error',
                'Hanya pengajuan yang sudah dibatalkan yang bisa dihapus.'
            );
        }

        $leaveRequest->delete();

        return redirect()
            ->route('leave.my-requests')
            ->with(
                'success',
                'Pengajuan cuti berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER HITUNG HARI KERJA
    |--------------------------------------------------------------------------
    */

    private function calculateWorkingDays($startDate, $endDate)
    {
        $totalDays = 0;

        $current = $startDate->copy();

        while ($current <= $endDate) {

            if (!$current->isWeekend()) {
                $totalDays++;
            }

            $current->addDay();
        }

        return $totalDays;
    }
}