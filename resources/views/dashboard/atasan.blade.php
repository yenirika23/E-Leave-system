@extends('layouts.app')

@section('title', 'Dashboard Atasan')
@section('page-title', 'Dashboard Atasan')
@section('breadcrumb', 'Selamat datang, ' . Auth::user()->full_name)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-num">{{ $stats['menunggu'] }}</div>
                <div class="stat-lbl">Menunggu Approval</div>
                <div class="stat-sub">Perlu tindakan segera</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['disetujui'] }}</div>
                <div class="stat-lbl">Disetujui</div>
                <div class="stat-sub">Periode {{ $annualLeaveData['quota_year'] ?? now()->year }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-num">{{ $stats['total_bawahan'] }}</div>
                <div class="stat-lbl">Total Bawahan</div>
                <div class="stat-sub">Dalam tim Anda</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
                <div class="stat-num">{{ $annualLeaveData['remaining_quota'] }}</div>
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

<div class="row g-4 mb-4">
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
</div>

<div class="mb-4 d-flex flex-wrap gap-2">
    <a href="{{ route('leave.create') }}" class="btn-blue" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
        <i class="bi bi-plus-circle-fill"></i> Ajukan Cuti
    </a>
    <a href="{{ route('leave.my-requests') }}" class="btn-blue-soft" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
        <i class="bi bi-clock-history"></i> Riwayat Cuti
    </a>
</div>

{{-- Daftar pengajuan yang menunggu --}}
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clipboard-check-fill me-2 text-primary"></i>Pengajuan Menunggu Persetujuan</span>
        <a href="{{ route('leave.approval-list') }}" class="btn-blue-soft">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        @forelse($pendingRequests as $req)
            <div style="padding:15px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">

                {{-- Avatar --}}
                <div class="avatar avatar-blue" style="width:42px;height:42px;border-radius:10px;font-size:14px;">
                    {{ strtoupper(substr($req->user->full_name, 0, 2)) }}
                </div>

                {{-- Info --}}
                <div style="flex:1;min-width:150px;">
                    <div style="font-weight:700;font-size:14px;color:#0f2554;">{{ $req->user->full_name }}</div>
                    <div style="font-size:12px;color:#64748b;">
                        {{ $req->leaveType->name }} ·
                        {{ $req->start_date->format('d M') }} – {{ $req->end_date->format('d M Y') }} ·
                        <b>{{ $req->total_days }} hari</b>
                    </div>
                    <div style="font-size:11.5px;color:#94a3b8;margin-top:1px;">
                        Diajukan {{ $req->request_date->diffForHumans() }}
                    </div>
                </div>

                {{-- Alasan singkat --}}
                <div style="max-width:180px;font-size:12px;color:#64748b;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                    {{ $req->reason }}
                </div>

                {{-- Tombol aksi cepat --}}
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('leave.approval-show', $req) }}" class="btn-blue-soft" style="font-size:12px;padding:6px 12px;">
                        <i class="bi bi-eye"></i> Detail
                    </a>
                    <form method="POST" action="{{ route('leave.approve', $req) }}" style="display:inline;"
                          onsubmit="return confirm('Setujui cuti {{ $req->user->full_name }}?')">
                        @csrf
                        <input type="hidden" name="action" value="disetujui">
                        <button type="submit" class="btn-green-soft" style="font-size:12px;padding:6px 12px;cursor:pointer;border:none;">
                            <i class="bi bi-check-lg"></i> Setujui
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-check-all"></i></div>
                <div class="empty-state-title">Tidak ada pengajuan menunggu</div>
                <div class="empty-state-desc">Semua pengajuan telah diproses</div>
            </div>
        @endforelse
    </div>
</div>
@endsection