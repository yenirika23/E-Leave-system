@extends('layouts.app')

@section('title', 'Dashboard Karyawan')
@section('page-title', 'Dashboard')
@section('breadcrumb', 'Selamat datang, ' . Auth::user()->full_name)

@section('content')
<div class="row g-3 mb-4">

    {{-- Stat: Total Pengajuan --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-send-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['total_pengajuan'] }}</div>
                <div class="stat-lbl">Total Pengajuan</div>
                <div class="stat-sub">Sepanjang waktu</div>
            </div>
        </div>
    </div>

    {{-- Stat: Menunggu --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-num">{{ $stats['menunggu'] }}</div>
                <div class="stat-lbl">Menunggu</div>
                <div class="stat-sub">Belum diproses</div>
            </div>
        </div>
    </div>

    {{-- Stat: Disetujui --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['disetujui'] }}</div>
                <div class="stat-lbl">Disetujui</div>
                <div class="stat-sub">Periode {{ $annualLeaveData['quota_year'] ?? now()->year }}</div>
            </div>
        </div>
    </div>

    {{-- Stat: Sisa Kuota --}}
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-calendar2-check-fill"></i></div>
            <div>
                <div class="stat-num">
                    {{ $annualLeaveData['remaining_quota'] ?? 0 }}
                </div>
                <div class="stat-lbl">Sisa Cuti Tahunan</div>
                <div class="stat-sub">Periode {{ $annualLeaveData['quota_year'] ?? now()->year }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-calendar3-event-fill me-2 text-primary"></i>Ringkasan Cuti Tahunan</span>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-6">
                        <div class="summary-item"><strong>Total Kuota</strong></div>
                        <div>{{ $annualLeaveData['total_quota'] ?? 0 }} hari</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Terpakai</strong></div>
                        <div>{{ $annualLeaveData['used_quota'] ?? 0 }} hari</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Sisa</strong></div>
                        <div>{{ $annualLeaveData['remaining_quota'] ?? 0 }} hari</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Carry Over</strong></div>
                        <div>{{ $annualLeaveData['carry_over'] ?? 0 }} hari</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Expired Days</strong></div>
                        <div>{{ $annualLeaveData['expired_days'] ?? 0 }} hari</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Anniversary berikutnya</strong></div>
                        <div>{{ $annualLeaveData['next_anniversary'] ? $annualLeaveData['next_anniversary']->format('d M Y') : '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="summary-item"><strong>Hari menuju hangus</strong></div>
                        <div>{{ $annualLeaveData['days_until_expiry'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($annualLeaveData['expiry_warning'])
        <div class="col-lg-6">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning bg-opacity-10">
                    <span><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Perhatian: Cutimu Akan Hangus</span>
                </div>
                <div class="card-body">
                    <p><strong>Anda memiliki {{ $annualLeaveData['expiry_warning']['total_remaining'] }} hari sisa cuti.</strong></p>
                    <p>Pada tanggal <strong>{{ $annualLeaveData['expiry_warning']['next_anniversary']->format('d M Y') }}</strong>, sebanyak <strong>{{ $annualLeaveData['expiry_warning']['expired_days'] }} hari</strong> akan hangus karena batas carry over maksimal hanya 10 hari.</p>
                    <p>Segera gunakan cuti Anda sebelum tanggal tersebut.</p>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="row g-4">

    {{-- Kuota Cuti --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Kuota Cuti {{ now()->year }}</span>
            </div>
            <div class="card-body">
                @forelse($myQuotas as $quota)
                    @php
                        $pct = $quota->total_quota > 0
                            ? round(($quota->used_quota / $quota->total_quota) * 100)
                            : 0;
                        $colors = ['#1a56db','#16a34a','#9333ea','#d97706','#dc2626'];
                        $c = $colors[$loop->index % count($colors)];
                    @endphp
                    <div class="mb-4">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                            <span style="font-size:13px;font-weight:700;color:#334155;">
                                {{ $quota->leaveType->name }}
                            </span>
                            <span style="font-size:12px;color:#64748b;">
                                <b style="color:#0f2554;">{{ $quota->remaining_quota }}</b> / {{ $quota->total_quota }} hari
                            </span>
                        </div>
                        <div class="quota-bar">
                            <div class="quota-bar-fill"
                                 style="width:{{ $pct }}%;background:{{ $c }};"></div>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:3px;">
                            Terpakai {{ $quota->used_quota }} hari ({{ $pct }}%)
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding:30px 10px;">
                        <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                        <div class="empty-state-title">Belum ada kuota</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pengajuan Terbaru --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Pengajuan Terbaru</span>
                <a href="{{ route('leave.create') }}" class="btn-blue" style="padding:7px 14px;font-size:12.5px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                    <i class="bi bi-plus-lg"></i> Ajukan Cuti
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($myRequests as $req)
                    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid #f1f5f9;">
                        <div class="avatar avatar-blue" style="border-radius:10px;width:40px;height:40px;">
                            <i class="bi bi-calendar2" style="font-size:17px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:13.5px;color:#1e293b;">
                                {{ $req->leaveType->name }}
                            </div>
                            <div style="font-size:12px;color:#64748b;margin-top:1px;">
                                {{ $req->start_date->format('d M') }} – {{ $req->end_date->format('d M Y') }}
                                · {{ $req->total_days }} hari
                            </div>
                        </div>
                        <span class="status-badge status-{{ $req->status }}">
                            {{ ucfirst($req->status) }}
                        </span>
                        <a href="{{ route('leave.show', $req) }}" class="btn-blue-soft" style="font-size:12px;padding:5px 10px;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                        <div class="empty-state-title">Belum ada pengajuan</div>
                        <div class="empty-state-desc">Klik tombol "Ajukan Cuti" untuk membuat pengajuan baru</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- Info atasan --}}
@if(Auth::user()->supervisor)
<div class="card mt-4">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
        <div class="stat-icon blue" style="width:46px;height:46px;font-size:20px;flex-shrink:0;">
            <i class="bi bi-person-check-fill"></i>
        </div>
        <div>
            <div style="font-size:12px;color:#64748b;font-weight:600;">Atasan Anda</div>
            <div style="font-size:15px;font-weight:700;color:#0f2554;">
                {{ Auth::user()->supervisor->full_name }}
            </div>
            <div style="font-size:12px;color:#94a3b8;">
                {{ Auth::user()->supervisor->position }} · {{ Auth::user()->supervisor->email }}
            </div>
        </div>
    </div>
</div>
@endif
@endsection