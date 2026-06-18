@extends('layouts.app')
@section('title', 'Laporan Cuti')
@section('page-title', 'Laporan Cuti Karyawan')
@section('breadcrumb', 'HRD › Laporan Cuti')

@section('content')

{{-- Filter ─────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body p-4">
        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach(['menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak','dibatalkan'=>'Dibatalkan'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Jenis Cuti</label>
                    <select name="leave_type_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label">Departemen</label>
                    <select name="department_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-xl-2">
                    <label class="form-label">Bulan</label>
                    <select name="month" class="form-select">
                        <option value="">Semua</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('id')->monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-xl-2">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-select">
                        @foreach(range(now()->year, now()->year - 4) as $y)
                            <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 col-xl-2">
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn-blue" style="padding:9px 14px;flex:1;">
                            <i class="bi bi-funnel-fill"></i>
                        </button>
                        <a href="{{ route('hrd.leave-report') }}" class="btn-blue-soft" style="padding:9px 12px;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tombol Export ────────────────────────────────── --}}
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:13px;color:#64748b;font-weight:600;">Export:</span>

    {{-- Excel --}}
    <a href="{{ route('hrd.leave-report.excel', request()->query()) }}"
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

    {{-- PDF --}}
    <a href="{{ route('hrd.leave-report.pdf', request()->query()) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:9px;
              background:#fff1f2;color:#b91c1c;border:1.5px solid #fecdd3;
              font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;"
       onmouseover="this.style.background='#fee2e2'"
       onmouseout="this.style.background='#fff1f2'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 2v6h6M9 15h6M9 11h6" stroke="#b91c1c" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Download PDF
    </a>

    <a href="{{ route('hrd.annual-leave-quota') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:9px;
              background:#eef2ff;color:#4338ca;border:1.5px solid #c7d2fe;
              font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;"
       onmouseover="this.style.background='#e0e7ff'"
       onmouseout="this.style.background='#eef2ff'">
        <i class="bi bi-calendar2-range"></i>
        Laporan Kuota Cuti Tahunan
    </a>

    <span style="font-size:12px;color:#94a3b8;margin-left:4px;">
        <i class="bi bi-info-circle me-1"></i>Filter yang aktif akan ikut ter-export
    </span>
</div>

{{-- Stat Cards ─────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $statItems = [
            ['label'=>'Total Data',       'val'=>$stats['total'],      'icon'=>'bi-list-check',       'color'=>'blue'],
            ['label'=>'Menunggu',         'val'=>$stats['menunggu'],   'icon'=>'bi-hourglass-split',  'color'=>'yellow'],
            ['label'=>'Disetujui',        'val'=>$stats['disetujui'],  'icon'=>'bi-check-circle-fill','color'=>'green'],
            ['label'=>'Ditolak',          'val'=>$stats['ditolak'],    'icon'=>'bi-x-circle-fill',    'color'=>'red'],
            ['label'=>'Total Hari Cuti',  'val'=>$stats['total_hari'],'icon'=>'bi-calendar-check',   'color'=>'teal'],
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

{{-- Grafik ─────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <span><i class="bi bi-bar-chart-fill me-2"></i>Tren Pengajuan Cuti per Bulan</span>
            </div>
            <div class="card-body" style="min-height:300px;">
                <canvas id="leaveTrendChart" height="130"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <span><i class="bi bi-pie-chart-fill me-2"></i>Proporsi Status Pengajuan</span>
            </div>
            <div class="card-body" style="min-height:300px;">
                <canvas id="statusDonutChart" height="260"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <span><i class="bi bi-bar-chart-line-fill me-2"></i>Jumlah Hari Cuti per Departemen</span>
            </div>
            <div class="card-body" style="min-height:300px;">
                <canvas id="departmentBarChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Tabel ────────────────────────────────────────── --}}
<div class="card">
    <div style="overflow-x:auto;">
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width:36px;">No</th>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Jenis Cuti</th>
                    <th>Periode</th>
                    <th>Hari</th>
                    <th>Status</th>
                    <th>Diproses Oleh</th>
                    <th>Tgl. Ajukan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveRequests as $req)
                    <tr>
                        <td style="color:#94a3b8;font-size:12px;text-align:center;">
                            {{ ($leaveRequests->currentPage()-1) * $leaveRequests->perPage() + $loop->iteration }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px;">
                                <div class="avatar avatar-blue" style="font-size:11px;flex-shrink:0;">
                                    {{ strtoupper(substr($req->user->full_name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:13px;color:#0f2554;">{{ $req->user->full_name }}</div>
                                    <div style="font-size:11px;color:#94a3b8;">{{ $req->user->nik }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:12.5px;color:#64748b;">{{ $req->user->department?->name ?? '—' }}</td>
                        <td>
                            <div style="font-size:13px;font-weight:600;">{{ $req->leaveType->name }}</div>
                            @if($req->day_type !== 'full')
                                <div style="font-size:11px;color:#ca8a04;">{{ $req->getDayTypeLabel() }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:12.5px;white-space:nowrap;">
                                {{ $req->start_date->format('d M Y') }}
                                @if($req->start_date != $req->end_date)
                                    <br><span style="color:#94a3b8;">s/d {{ $req->end_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="font-weight:700;color:#0f2554;white-space:nowrap;">
                            {{ $req->getTotalLabel() }}
                        </td>
                        <td>
                            <span class="status-badge status-{{ $req->status }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td style="font-size:12px;color:#64748b;">
                            {{ $req->approver?->full_name ?? '—' }}
                            @if($req->approved_at)
                                <div style="font-size:11px;color:#94a3b8;">{{ $req->approved_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td style="font-size:12px;color:#94a3b8;white-space:nowrap;">
                            {{ $req->request_date->format('d M Y') }}
                        </td>
                        <td>
                            <a href="{{ route('hrd.leave-request-detail', $req) }}" class="btn-blue-soft" style="font-size:11px;padding:4px 8px;">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">
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

@if($leaveRequests->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $leaveRequests->appends(request()->query())->links() }}
    </div>
@endif
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartLabels = @json($chartLabels);
    const chartMonthly = @json($chartMonthly);
    const chartStatus = @json($chartStatus);
    const chartDepartmentData = @json($chartDepartmentData);

    const trendCtx = document.getElementById('leaveTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Disetujui',
                        data: chartMonthly.map(r => r.disetujui),
                        backgroundColor: '#2563eb',
                    },
                    {
                        label: 'Ditolak',
                        data: chartMonthly.map(r => r.ditolak),
                        backgroundColor: '#dc2626',
                    },
                    {
                        label: 'Menunggu',
                        data: chartMonthly.map(r => r.menunggu),
                        backgroundColor: '#f59e0b',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true, title: { display: true, text: 'Jumlah Pengajuan' } },
                },
            }
        });
    }

    const statusCtx = document.getElementById('statusDonutChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Disetujui', 'Ditolak', 'Menunggu'],
                datasets: [{
                    data: [chartStatus.disetujui, chartStatus.ditolak, chartStatus.menunggu],
                    backgroundColor: ['#22c55e', '#ef4444', '#facc15'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { enabled: true },
                },
            }
        });
    }

    const departmentCtx = document.getElementById('departmentBarChart');
    if (departmentCtx) {
        const labels = Object.keys(chartDepartmentData);
        const values = Object.values(chartDepartmentData);

        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Jumlah Hari Cuti',
                    data: values,
                    backgroundColor: '#2563eb',
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Total Hari' } },
                    y: { title: { display: true, text: 'Departemen' } },
                },
            }
        });
    }
</script>
@endsection