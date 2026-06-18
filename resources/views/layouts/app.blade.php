<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'E-Leave') — Sistem Cuti Karyawan</title>

    {{-- Google Fonts: Nunito untuk body, Plus Jakarta Sans untuk heading --}}
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ============================================
           CSS VARIABLES — Tema warna biru
        ============================================ */
        :root {
            --navy:        #0f2554;   /* Biru gelap utama */
            --blue:        #1a56db;   /* Biru cerah */
            --blue-light:  #3b82f6;   /* Biru muda */
            --blue-pale:   #eff6ff;   /* Biru sangat pucat (background) */
            --blue-mid:    #dbeafe;   /* Biru pucat (border, hover) */
            --sidebar-w:   265px;
            --header-h:    64px;
            --radius:      12px;
            --shadow-sm:   0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            --shadow-md:   0 4px 16px rgba(15,37,84,.12);
            --shadow-lg:   0 10px 40px rgba(15,37,84,.18);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f0f4ff;
            color: #1e293b;
            font-size: 14px;
            margin: 0;
        }

        h1,h2,h3,h4,h5,h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ============================================
           SIDEBAR
        ============================================ */
        .sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: var(--navy);
            display: flex;
            flex-direction: column;
            z-index: 999;
            overflow: hidden;
        }

        /* Pola geometri tipis di background sidebar */
        .sidebar::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(59,130,246,.12);
            pointer-events: none;
        }
        .sidebar::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(26,86,219,.15);
            pointer-events: none;
        }

        /* Brand / Logo */
        .sb-brand {
            padding: 22px 20px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            position: relative;
            z-index: 1;
        }

        .sb-logo {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-light) 100%);
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(26,86,219,.4);
            flex-shrink: 0;
        }

        .sb-logo i { font-size: 20px; color: #fff; }

        .sb-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 18px; font-weight: 800;
            color: #fff;
            line-height: 1.1;
        }
        .sb-title span { color: #60a5fa; }
        .sb-subtitle { font-size: 10px; color: rgba(255,255,255,.35); font-weight: 400; letter-spacing: .5px; }

        /* User info card di sidebar */
        .sb-user {
            margin: 14px 14px 6px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px;
            padding: 12px 14px;
            position: relative;
            z-index: 1;
        }
        .sb-user-name  { font-size: 13px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-user-pos   { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 2px; }
        .sb-role-badge {
            display: inline-block;
            background: rgba(96,165,250,.25);
            color: #93c5fd;
            border: 1px solid rgba(96,165,250,.3);
            border-radius: 20px;
            font-size: 10px; font-weight: 700;
            padding: 2px 8px;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* Nav menu */
        .sb-nav { flex: 1; overflow-y: auto; padding: 8px 0; position: relative; z-index: 1; }
        .sb-nav::-webkit-scrollbar { width: 0; }

        .sb-section {
            font-size: 10px; font-weight: 700;
            color: rgba(255,255,255,.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 20px 5px;
        }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px 9px 20px;
            margin: 1px 10px;
            border-radius: 9px;
            color: rgba(255,255,255,.6);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all .18s ease;
            position: relative;
        }
        .sb-link i { font-size: 16px; width: 18px; text-align: center; flex-shrink: 0; }

        .sb-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .sb-link.active {
            background: linear-gradient(90deg, rgba(26,86,219,.7) 0%, rgba(59,130,246,.4) 100%);
            color: #fff;
            box-shadow: inset 3px 0 0 #60a5fa;
        }
        .sb-link.active i { color: #93c5fd; }

        /* Badge angka di menu */
        .sb-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            border-radius: 10px;
            font-size: 10px; font-weight: 700;
            padding: 1px 6px;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }

        /* Footer sidebar (logout) */
        .sb-footer {
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,.07);
            position: relative;
            z-index: 1;
        }
        .sb-logout {
            display: flex; align-items: center; gap: 9px;
            width: 100%; background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 13px; font-weight: 600;
            cursor: pointer;
            transition: all .18s;
        }
        .sb-logout:hover { background: rgba(239,68,68,.25); color: #fecaca; }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-wrap {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top header */
        .top-bar {
            height: var(--header-h);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .top-bar-left { display: flex; align-items: center; gap: 14px; }
        .page-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 17px; font-weight: 700;
            color: var(--navy);
            margin: 0;
        }

        /* Breadcrumb halus */
        .page-breadcrumb { font-size: 12px; color: #94a3b8; margin-top: 1px; }

        .top-bar-right { display: flex; align-items: center; gap: 12px; }

        .top-date {
            background: var(--blue-pale);
            border: 1px solid var(--blue-mid);
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 12px;
            color: var(--blue);
            font-weight: 600;
        }

        /* Konten halaman */
        .page-content { flex: 1; padding: 26px 28px; }

        /* ============================================
           KOMPONEN UMUM
        ============================================ */

        /* Alert flash messages */
        .flash-alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .flash-success { background: #dcfce7; color: #15803d; }
        .flash-danger  { background: #fee2e2; color: #b91c1c; }
        .flash-info    { background: #dbeafe; color: #1d4ed8; }
        .flash-warning { background: #fef9c3; color: #854d0e; }

        /* Card umum */
        .card {
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 16px 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: var(--navy);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-body { padding: 20px; }

        /* Stat card dashboard */
        .stat-card {
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: var(--radius);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.blue   { background: #dbeafe; color: var(--blue); }
        .stat-icon.green  { background: #dcfce7; color: #16a34a; }
        .stat-icon.yellow { background: #fef9c3; color: #ca8a04; }
        .stat-icon.red    { background: #fee2e2; color: #dc2626; }
        .stat-icon.purple { background: #f3e8ff; color: #9333ea; }
        .stat-icon.teal   { background: #ccfbf1; color: #0d9488; }

        .stat-num {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 28px; font-weight: 800;
            color: var(--navy);
            line-height: 1;
        }
        .stat-lbl { font-size: 12.5px; color: #64748b; margin-top: 3px; font-weight: 500; }
        .stat-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* Status badge cuti */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-menunggu  { background: #fef9c3; color: #854d0e; }
        .status-menunggu::before  { background: #ca8a04; }
        .status-disetujui { background: #dcfce7; color: #15803d; }
        .status-disetujui::before { background: #16a34a; }
        .status-ditolak   { background: #fee2e2; color: #b91c1c; }
        .status-ditolak::before   { background: #dc2626; }
        .status-dibatalkan { background: #f1f5f9; color: #475569; }
        .status-dibatalkan::before { background: #94a3b8; }

        /* Tabel */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom thead tr th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 11px 16px;
            border-bottom: 2px solid #e8edf5;
        }
        .table-custom thead tr th:first-child { border-radius: 10px 0 0 0; }
        .table-custom thead tr th:last-child  { border-radius: 0 10px 0 0; }
        .table-custom tbody tr td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13.5px;
            vertical-align: middle;
        }
        .table-custom tbody tr:last-child td { border-bottom: none; }
        .table-custom tbody tr:hover td { background: #f8faff; }

        /* Form controls */
        .form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 5px; }
        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 9px 13px;
            font-size: 13.5px;
            font-family: 'Nunito', sans-serif;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
            outline: none;
        }
        .input-group-text {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            color: #94a3b8;
            border-radius: 9px 0 0 9px;
        }
        .input-group .form-control { border-left: none; border-radius: 0 9px 9px 0; }

        /* Buttons */
        .btn-blue {
            background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 100%);
            color: #fff; border: none;
            border-radius: 9px;
            padding: 9px 18px;
            font-weight: 700;
            font-size: 13.5px;
            font-family: 'Nunito', sans-serif;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 2px 8px rgba(26,86,219,.3);
        }
        .btn-blue:hover {
            box-shadow: 0 4px 16px rgba(26,86,219,.4);
            transform: translateY(-1px);
            color: #fff;
        }
        .btn-blue-soft {
            background: var(--blue-pale);
            color: var(--blue);
            border: 1.5px solid var(--blue-mid);
            border-radius: 9px;
            padding: 7px 14px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px;
            transition: all .15s;
        }
        .btn-blue-soft:hover { background: var(--blue-mid); color: var(--blue); }
        .btn-green-soft {
            background: #f0fdf4; color: #15803d;
            border: 1.5px solid #bbf7d0; border-radius: 9px;
            padding: 7px 14px; font-size: 12.5px; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            transition: all .15s;
        }
        .btn-green-soft:hover { background: #dcfce7; color: #15803d; }
        .btn-red-soft {
            background: #fff1f2; color: #b91c1c;
            border: 1.5px solid #fecdd3; border-radius: 9px;
            padding: 7px 14px; font-size: 12.5px; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            transition: all .15s;
        }
        .btn-red-soft:hover { background: #fee2e2; color: #b91c1c; }
        .btn-yellow-soft {
            background: #fffbeb; color: #92400e;
            border: 1.5px solid #fde68a; border-radius: 9px;
            padding: 7px 14px; font-size: 12.5px; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            transition: all .15s;
        }
        .btn-yellow-soft:hover { background: #fef3c7; color: #92400e; }

        /* Avatar inisial */
        .avatar {
            width: 36px; height: 36px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px;
            flex-shrink: 0;
        }
        .avatar-blue   { background: #dbeafe; color: var(--blue); }
        .avatar-green  { background: #dcfce7; color: #16a34a; }
        .avatar-purple { background: #f3e8ff; color: #9333ea; }
        .avatar-orange { background: #ffedd5; color: #ea580c; }

        /* Progress bar kuota cuti */
        .quota-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        .quota-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width .5s ease;
        }

        /* Timeline tracking */
        .timeline { position: relative; padding-left: 28px; }
        .timeline::before {
            content: ''; position: absolute;
            left: 9px; top: 0; bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--blue) 0%, #e2e8f0 100%);
        }
        .timeline-item { position: relative; padding-bottom: 20px; }
        .timeline-dot {
            position: absolute; left: -24px; top: 3px;
            width: 14px; height: 14px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--blue);
        }
        .timeline-dot.done  { background: var(--blue); box-shadow: 0 0 0 2px var(--blue); }
        .timeline-dot.wait  { background: #fbbf24; box-shadow: 0 0 0 2px #fbbf24; }
        .timeline-dot.empty { background: #e2e8f0; box-shadow: 0 0 0 2px #cbd5e1; }
        .timeline-title { font-weight: 700; font-size: 13px; color: #1e293b; }
        .timeline-time  { font-size: 11.5px; color: #94a3b8; margin-top: 2px; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px;
        }
        .empty-state-icon {
            width: 72px; height: 72px;
            background: var(--blue-pale);
            border-radius: 20px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 32px; color: var(--blue);
            margin-bottom: 16px;
        }
        .empty-state-title { font-weight: 700; color: #1e293b; font-size: 16px; }
        .empty-state-desc  { color: #64748b; font-size: 13px; margin-top: 4px; }

        /* Pagination */
        .pagination .page-link {
            border-radius: 7px !important;
            margin: 0 2px;
            border: 1.5px solid #e2e8f0;
            color: var(--blue);
            font-size: 13px; font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background: var(--blue);
            border-color: var(--blue);
        }
    </style>

    @yield('styles')
</head>
<body>

{{-- ============================== SIDEBAR ============================== --}}
<aside class="sidebar">
    {{-- Logo --}}
    <div class="sb-brand">
        <div class="sb-logo">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
        <div>
            <div class="sb-title">E-<span>Leave</span></div>
            <div class="sb-subtitle">Sistem Cuti Karyawan</div>
        </div>
    </div>

    {{-- Info user --}}
    <div class="sb-user">
        <div class="sb-user-name">{{ Auth::user()->full_name }}</div>
        <div class="sb-user-pos">{{ Auth::user()->position ?? 'Karyawan' }}</div>
        <div><span class="sb-role-badge">{{ Auth::user()->role }}</span></div>
    </div>

    {{-- Menu navigasi --}}
    <nav class="sb-nav">
        <div class="sb-section">Umum</div>
        <a href="{{ route('dashboard') }}" class="sb-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        {{-- MENU KARYAWAN --}}
        @if(Auth::user()->isKaryawan())
            <div class="sb-section">Cuti Saya</div>
            <a href="{{ route('leave.create') }}" class="sb-link {{ request()->routeIs('leave.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle-fill"></i> Ajukan Cuti
            </a>
            <a href="{{ route('leave.my-requests') }}" class="sb-link {{ request()->routeIs('leave.my-requests') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Riwayat Cuti
            </a>
        @endif

        {{-- MENU ATASAN --}}
        @if(Auth::user()->isAtasan())
            <div class="sb-section">Cuti Saya</div>
            <a href="{{ route('leave.create') }}" class="sb-link {{ request()->routeIs('leave.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle-fill"></i> Ajukan Cuti
            </a>
            <a href="{{ route('leave.my-requests') }}" class="sb-link {{ request()->routeIs('leave.my-requests') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Riwayat Cuti
            </a>
            <div class="sb-section">Persetujuan</div>
            <a href="{{ route('leave.approval-list') }}" class="sb-link {{ request()->routeIs('leave.approval-list') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check-fill"></i> Approval Cuti
                @php
                    $pendingCount = \App\Models\LeaveRequest::whereHas('user', fn($q) =>
                        $q->where('supervisor_id', Auth::id()))
                        ->where('status', 'menunggu')->count();
                @endphp
                @if($pendingCount > 0)
                    <span class="sb-badge">{{ $pendingCount }}</span>
                @endif
            </a>
        @endif

        {{-- MENU HRD --}}
        @if(Auth::user()->isHrd())
            <div class="sb-section">Manajemen</div>
            <a href="{{ route('hrd.users.index') }}" class="sb-link {{ request()->routeIs('hrd.users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Data Karyawan
            </a>
            <a href="{{ route('hrd.leave-report') }}" class="sb-link {{ request()->routeIs('hrd.leave-report') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i> Laporan Cuti
            </a>
            <a href="{{ route('hrd.leave-approval') }}" class="sb-link {{ request()->routeIs('hrd.leave-approval') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i> Persetujuan HR
                @php
                    $hrPendingCount = \App\Models\LeaveRequest::where('status', 'menunggu')
                        ->whereHas('user.supervisor', fn($q) =>
                            $q->where('status_aktif', 'cuti')
                        )
                        ->count();
                @endphp
                @if($hrPendingCount > 0)
                    <span class="sb-badge">{{ $hrPendingCount }}</span>
                @endif
            </a>
        @endif
    </nav>

    {{-- Logout --}}
    <div class="sb-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sb-logout">
                <i class="bi bi-box-arrow-left"></i> Keluar dari Sistem
            </button>
        </form>
    </div>
</aside>

{{-- ============================== MAIN ============================== --}}
<div class="main-wrap">

    {{-- Top bar --}}
    <header class="top-bar">
        <div class="top-bar-left">
            <div>
                <div class="page-heading">@yield('page-title', 'Dashboard')</div>
                <div class="page-breadcrumb">@yield('breadcrumb', 'E-Leave')</div>
            </div>
        </div>
        <div class="top-bar-right">
            <div class="top-date">
                <i class="bi bi-calendar3 me-1"></i>
                {{ now()->locale('id')->isoFormat('dddd, D MMM Y') }}
            </div>
        </div>
    </header>

    {{-- Konten --}}
    <main class="page-content">

        {{-- Flash messages --}}
        @foreach(['success' => 'flash-success', 'error' => 'flash-danger', 'info' => 'flash-info', 'warning' => 'flash-warning'] as $key => $cls)
            @if(session($key))
                <div class="flash-alert {{ $cls }} alert-dismissible fade show" role="alert">
                    <i class="bi bi-{{ $key === 'success' ? 'check-circle-fill' : ($key === 'error' ? 'x-circle-fill' : ($key === 'warning' ? 'exclamation-triangle-fill' : 'info-circle-fill')) }}"></i>
                    {{ session($key) }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>