@extends('layouts.app')

@section('title', 'Dashboard HRD')
@section('page-title', 'Dashboard HRD')
@section('breadcrumb', 'Selamat datang, ' . Auth::user()->full_name)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['total_karyawan'] }}</div>
                <div class="stat-lbl">Total Karyawan</div>
                <div class="stat-sub">Aktif dalam sistem</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-person-workspace"></i></div>
            <div>
                <div class="stat-num">{{ $stats['total_atasan'] }}</div>
                <div class="stat-lbl">Total Atasan</div>
                <div class="stat-sub">Supervisor/Manager</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-num">{{ $stats['cuti_menunggu'] }}</div>
                <div class="stat-lbl">Cuti Menunggu</div>
                <div class="stat-sub">Belum diproses</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['cuti_disetujui'] }}</div>
                <div class="stat-lbl">Cuti Disetujui</div>
                <div class="stat-sub">Tahun {{ now()->year }}</div>
            </div>
        </div>
    </div>
</div>

@if($stats['supervisors_on_leave'] > 0 || $stats['hr_pending_approvals'] > 0)
    <div class="card mb-4" style="border-left:4px solid #1d4ed8;">
        <div class="card-body p-4" style="background:#eff6ff;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div style="font-weight:700;color:#1e3a8a;">Notifikasi HR</div>
                <div style="font-size:13px;color:#1e3a8a;">
                    @if($stats['supervisors_on_leave'] > 0)
                        {{ $stats['supervisors_on_leave'] }} atasan sedang cuti.
                    @endif
                    @if($stats['hr_pending_approvals'] > 0)
                        @if($stats['supervisors_on_leave'] > 0) • @endif
                        {{ $stats['hr_pending_approvals'] }} pengajuan perlu diproses oleh HR.
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Shortcut aksi --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ route('hrd.users.create') }}" style="text-decoration:none;">
            <div style="background:linear-gradient(135deg,#0f2554 0%,#1a56db 100%);border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:16px;color:#fff;transition:all .2s;" class="stat-card" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <div style="width:50px;height:50px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:15px;">Tambah Karyawan Baru</div>
                    <div style="font-size:12.5px;opacity:.75;margin-top:2px;">Daftarkan karyawan & atur atasan</div>
                </div>
                <i class="bi bi-arrow-right-circle ms-auto fs-4" style="opacity:.6;"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('hrd.leave-report') }}" style="text-decoration:none;">
            <div style="background:#fff;border:1.5px solid #e8edf5;border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:16px;color:#0f2554;transition:all .2s;" class="stat-card">
                <div style="width:50px;height:50px;background:#f0f4ff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#1a56db;flex-shrink:0;">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:15px;">Laporan Cuti</div>
                    <div style="font-size:12.5px;color:#64748b;margin-top:2px;">Filter & export data cuti</div>
                </div>
                <i class="bi bi-arrow-right-circle ms-auto fs-4" style="color:#94a3b8;"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('hrd.leave-approval') }}" style="text-decoration:none;">
            <div style="background:linear-gradient(135deg,#0f2554 0%,#0891b2 100%);border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:16px;color:#fff;transition:all .2s;" class="stat-card" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                <div style="width:50px;height:50px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:15px;">Persetujuan HR</div>
                    <div style="font-size:12.5px;opacity:.75;margin-top:2px;">Lihat pengajuan yang harus diproses HR.</div>
                </div>
                <i class="bi bi-arrow-right-circle ms-auto fs-4" style="opacity:.6;"></i>
            </div>
        </a>
    </div>
</div>

{{-- Tabel pengajuan terbaru --}}
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-list-check me-2 text-primary"></i>Pengajuan Cuti Terbaru</span>
        <a href="{{ route('hrd.leave-report') }}" class="btn-blue-soft">Lihat Laporan Lengkap</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="table-custom" style="width:100%;">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Jenis Cuti</th>
                    <th>Periode</th>
                    <th>Hari</th>
                    <th>Status</th>
                    <th>Atasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRequests as $req)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="avatar avatar-blue" style="font-size:12px;">
                                    {{ strtoupper(substr($req->user->full_name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:13px;">{{ $req->user->full_name }}</div>
                                    <div style="font-size:11.5px;color:#94a3b8;">{{ $req->user->nik }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $req->leaveType->name }}</td>
                        <td>
                            <div style="font-size:12.5px;">{{ $req->start_date->format('d M') }} – {{ $req->end_date->format('d M Y') }}</div>
                        </td>
                        <td><b>{{ $req->total_days }}</b> hari</td>
                        <td><span class="status-badge status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                        <td style="font-size:12.5px;color:#64748b;">{{ $req->user->supervisor?->full_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="empty-state" style="padding:40px;">
                            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                            <div class="empty-state-title">Belum ada pengajuan</div>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection