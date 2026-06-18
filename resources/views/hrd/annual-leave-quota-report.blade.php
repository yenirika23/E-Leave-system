@extends('layouts.app')
@section('title', 'Laporan Kuota Cuti Tahunan')
@section('page-title', 'Laporan Kuota Cuti Tahunan')
@section('breadcrumb', 'HRD › Laporan Kuota Cuti Tahunan')

@section('content')

<div class="card mb-4">
    <div class="card-body p-4">
        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Departemen</label>
                    <select name="department_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Tahun Periode</label>
                    <select name="year" class="form-select">
                        @foreach(range(now()->year, now()->year - 4) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-xl-2">
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn-blue" style="padding:9px 14px;flex:1;">
                            <i class="bi bi-funnel-fill"></i>
                        </button>
                        <a href="{{ route('hrd.annual-leave-quota') }}" class="btn-blue-soft" style="padding:9px 12px;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:13px;color:#64748b;font-weight:600;">Export:</span>

    <a href="{{ route('hrd.annual-leave-quota.excel', request()->query()) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:9px;
              background:#f0fdf4;color:#15803d;border:1.5px solid #bbf7d0;
              font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;"
       onmouseover="this.style.background='#dcfce7'"
       onmouseout="this.style.background='#f0fdf4'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#15803d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 2v6h6M8 13h2m-2 4h2m4-4h2m-2 4h2" stroke="#15803d" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Download Excel (.xlsx)
    </a>

    <span style="font-size:12px;color:#94a3b8;margin-left:4px;">
        <i class="bi bi-info-circle me-1"></i>Filter yang aktif akan ikut ter-export
    </span>
</div>

<div class="row g-3 mb-4">
    @php
        $statItems = [
            ['label'=>'Total Catatan',      'val'=>$stats['total_records'],   'icon'=>'bi-list-check',      'color'=>'blue'],
            ['label'=>'Total Kuota',        'val'=>$stats['total_quota'],     'icon'=>'bi-calendar4-week','color'=>'teal'],
            ['label'=>'Terpakai',           'val'=>$stats['used_quota'],      'icon'=>'bi-check-circle-fill','color'=>'green'],
            ['label'=>'Sisa',               'val'=>$stats['remaining_quota'], 'icon'=>'bi-calendar-check','color'=>'purple'],
            ['label'=>'Hari Hangus',        'val'=>$stats['expired_days'],    'icon'=>'bi-x-circle-fill',    'color'=>'red'],
        ];
    @endphp
    @foreach($statItems as $s)
        <div class="col-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon {{ $s['color'] }}"><i class="bi {{ $s['icon'] }}"></i></div>
                <div>
                    <div class="stat-num">{{ $s['val'] }}</div>
                    <div class="stat-lbl">{{ $s['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width:36px;">No</th>
                    <th>NIK</th>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Gabung</th>
                    <th>Periode</th>
                    <th>Total Kuota</th>
                    <th>Terpakai</th>
                    <th>Sisa</th>
                    <th>Carry Over</th>
                    <th>Hangus</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotas as $quota)
                    <tr>
                        <td style="color:#94a3b8;font-size:12px;text-align:center;">
                            {{ ($quotas->currentPage()-1) * $quotas->perPage() + $loop->iteration }}
                        </td>
                        <td style="font-size:12.5px;color:#64748b;">{{ $quota->user->nik }}</td>
                        <td>
                            <div style="font-weight:700;font-size:13px;color:#0f2554;">{{ $quota->user->full_name }}</div>
                        </td>
                        <td style="font-size:12.5px;color:#64748b;">{{ $quota->user->department?->name ?? '—' }}</td>
                        <td style="font-size:12.5px;color:#64748b;white-space:nowrap;">
                            {{ $quota->user->join_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td style="font-size:12.5px;white-space:nowrap;">
                            @if($quota->user->join_date)
                                {{ $quota->user->join_date->copy()->setYear($quota->year)->format('d M Y') }}
                                <br>
                                <span style="color:#94a3b8;">s/d {{ $quota->user->join_date->copy()->setYear($quota->year)->addYear()->subDay()->format('d M Y') }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-weight:700;color:#0f2554;">{{ $quota->total_quota }}</td>
                        <td style="font-weight:700;color:#0f2554;">{{ $quota->used_quota }}</td>
                        <td style="font-weight:700;color:#0f2554;">{{ $quota->remaining_quota }}</td>
                        <td style="font-size:12px;color:#64748b;">
                            {{ $quota->carried_over_from_year ? 'Dari ' . $quota->carried_over_from_year : '-' }}
                        </td>
                        <td style="font-size:12px;color:#64748b;">{{ $quota->expired_days ?? 0 }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $quota->getStatusLabel())) }}">
                                {{ $quota->getStatusLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12">
                        <div class="empty-state" style="padding:50px;">
                            <div class="empty-state-icon"><i class="bi bi-file-earmark-x"></i></div>
                            <div class="empty-state-title">Tidak ada data</div>
                            <div class="empty-state-desc">Coba ubah filter pencarian</div>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($quotas->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $quotas->appends(request()->query())->links() }}
    </div>
@endif
@endsection
