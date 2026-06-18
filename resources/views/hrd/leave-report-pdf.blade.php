<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Cuti Karyawan</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9.5px; color:#1e293b; padding:18px 22px; }

        /* ── Header ─────── */
        .hdr { display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:12px; border-bottom:3px solid #1a56db; margin-bottom:14px; }
        .hdr-left .company { font-size:15px; font-weight:700; color:#0f2554; }
        .hdr-left .title   { font-size:12px; font-weight:600; color:#1a56db; margin-top:3px; }
        .hdr-left .sub     { font-size:8.5px; color:#64748b; margin-top:2px; }
        .hdr-right         { text-align:right; font-size:8.5px; color:#64748b; }

        /* ── Stat boxes ─── */
        .stats { display:table; width:100%; margin-bottom:14px; border-collapse:separate; border-spacing:6px 0; }
        .stats-row { display:table-row; }
        .stat-box { display:table-cell; text-align:center; border:1.5px solid #e2e8f0; border-radius:8px; padding:7px 4px; }
        .stat-num { font-size:17px; font-weight:800; }
        .stat-lbl { font-size:7.5px; color:#64748b; margin-top:1px; }

        /* ── Table ──────── */
        table { width:100%; border-collapse:collapse; }
        thead tr th {
            background:#1a56db; color:#fff;
            padding:7px 7px; text-align:left;
            font-size:8px; font-weight:700;
            text-transform:uppercase; letter-spacing:.3px;
        }
        tbody tr td { padding:6px 7px; border-bottom:1px solid #e8edf5; vertical-align:middle; }
        tbody tr:nth-child(even) td { background:#f8faff; }
        .badge { border-radius:10px; padding:2px 7px; font-size:7.5px; font-weight:700; }
        .b-menunggu  { background:#fef9c3; color:#854d0e; }
        .b-disetujui { background:#dcfce7; color:#15803d; }
        .b-ditolak   { background:#fee2e2; color:#b91c1c; }
        .b-dibatalkan{ background:#f1f5f9; color:#475569; }

        /* ── Footer ─────── */
        .footer { margin-top:14px; border-top:1px solid #e2e8f0; padding-top:8px; display:flex; justify-content:space-between; font-size:8px; color:#94a3b8; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="hdr">
    <div class="hdr-left">
        <div class="company">🏭 PT. Semikonduktor Indonesia</div>
        <div class="title">LAPORAN DATA CUTI KARYAWAN</div>
        <div class="sub">
            Status: {{ $filterInfo['status'] }} &nbsp;|&nbsp;
            Bulan: {{ $filterInfo['bulan'] }} &nbsp;|&nbsp;
            Tahun: {{ $filterInfo['tahun'] }}
        </div>
    </div>
    <div class="hdr-right">
        Digenerate:<br>
        <b>{{ $filterInfo['digenerate'] }}</b><br><br>
        Sistem E-Leave v1.0
    </div>
</div>

{{-- Stat boxes --}}
<div class="stats">
    <div class="stats-row">
        @foreach([
            ['Total Pengajuan', $stats['total'],     '#0f2554'],
            ['Menunggu',        $stats['menunggu'],  '#ca8a04'],
            ['Disetujui',       $stats['disetujui'], '#16a34a'],
            ['Ditolak',         $stats['ditolak'],   '#dc2626'],
            ['Total Hari Disetujui', $stats['total_hari'].' hari', '#0f2554'],
        ] as $s)
        <div class="stat-box">
            <div class="stat-num" style="color:{{ $s[2] }};">{{ $s[1] }}</div>
            <div class="stat-lbl">{{ $s[0] }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Tabel --}}
<table>
    <thead>
        <tr>
            <th style="width:3%;">No</th>
            <th style="width:8%;">NIK</th>
            <th style="width:16%;">Nama Karyawan</th>
            <th style="width:12%;">Departemen</th>
            <th style="width:13%;">Jenis Cuti</th>
            <th style="width:10%;">Tgl Mulai</th>
            <th style="width:10%;">Tgl Selesai</th>
            <th style="width:8%;">Durasi</th>
            <th style="width:9%;">Status</th>
            <th style="width:11%;">Diproses Oleh</th>
        </tr>
    </thead>
    <tbody>
        @forelse($leaveRequests as $i => $req)
        <tr>
            <td style="text-align:center;color:#94a3b8;">{{ $i+1 }}</td>
            <td style="font-family:monospace;font-size:8.5px;">{{ $req->user->nik }}</td>
            <td>
                <b>{{ $req->user->full_name }}</b><br>
                <span style="color:#94a3b8;font-size:7.5px;">{{ $req->user->position }}</span>
            </td>
            <td style="color:#64748b;">{{ $req->user->department?->name ?? '-' }}</td>
            <td>
                {{ $req->leaveType->name }}
                @if($req->day_type !== 'full')
                    <br><span style="color:#ca8a04;font-size:7.5px;">{{ $req->getDayTypeLabel() }}</span>
                @endif
            </td>
            <td>{{ $req->start_date->format('d/m/Y') }}</td>
            <td>{{ $req->end_date->format('d/m/Y') }}</td>
            <td style="text-align:center;font-weight:700;">{{ $req->getTotalLabel() }}</td>
            <td>
                <span class="badge b-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
            </td>
            <td style="color:#64748b;">
                {{ $req->approver?->full_name ?? '-' }}
                @if($req->approved_at)
                    <br><span style="font-size:7.5px;color:#94a3b8;">{{ $req->approved_at->format('d/m/Y') }}</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center;padding:20px;color:#94a3b8;">Tidak ada data</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Footer --}}
<div class="footer">
    <span>Total: {{ $stats['total'] }} data &nbsp;|&nbsp; Disetujui: {{ $stats['disetujui'] }} data &nbsp;|&nbsp; Total hari disetujui: {{ $stats['total_hari'] }} hari</span>
    <span>E-Leave System &copy; {{ now()->year }} &nbsp;|&nbsp; PT. Semikonduktor Indonesia</span>
</div>

</body>
</html>