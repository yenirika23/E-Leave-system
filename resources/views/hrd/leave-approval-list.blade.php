@extends('layouts.app')
@section('title', 'Persetujuan HR')
@section('page-title', 'Daftar Pengajuan Cuti HR')
@section('breadcrumb', 'HRD › Persetujuan HR')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
                    <div>
                        <div style="font-size:18px;font-weight:700;color:#0f2554;">Daftar Pengajuan Cuti HR</div>
                        <div style="font-size:13px;color:#64748b;">Pengajuan yang dialihkan karena atasan sedang cuti.</div>
                    </div>
                    <a href="{{ route('hrd.leave-report') }}" class="btn-blue-soft d-inline-flex align-items-center" style="gap:8px;">
                        <i class="bi bi-arrow-left"></i> Kembali ke Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body p-4">
                <div style="font-size:12px;color:#64748b;">Total Pengajuan</div>
                <div style="font-size:28px;font-weight:800;color:#0f2554;">{{ $totalRequests }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari Karyawan / NIK</label>
                <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Nama atau NIK">
            </div>
            <div class="col-md-3">
                <label class="form-label">Departemen</label>
                <select name="department_id" class="form-select">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-blue" style="width:100%;">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('hrd.leave-approval') }}" class="btn-blue-soft" style="width:100%;">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Atasan</th>
                    <th>Jenis Cuti</th>
                    <th>Periode</th>
                    <th>Hari</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td style="text-align:center;color:#94a3b8;font-size:12px;">{{ ($requests->currentPage()-1) * $requests->perPage() + $loop->iteration }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
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
                        <td style="font-size:12.5px;color:#64748b;">{{ $req->user->supervisor?->full_name ?? '—' }}</td>
                        <td>{{ $req->leaveType->name }}</td>
                        <td style="white-space:nowrap;">{{ $req->start_date->format('d M Y') }}<br><span style="color:#94a3b8;">s/d {{ $req->end_date->format('d M Y') }}</span></td>
                        <td style="font-weight:700;color:#0f2554;">{{ $req->getTotalLabel() }}</td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="{{ route('hrd.leave-request-detail', $req) }}" class="btn-blue-soft" style="padding:6px 10px;font-size:12px;">Detail</a>
                            <form method="POST" action="{{ route('hrd.leave-approval.process', $req) }}" style="display:inline-flex;">
                                @csrf
                                <input type="hidden" name="action" value="disetujui">
                                <button type="submit" class="btn-green-soft" style="padding:6px 10px;font-size:12px;">Approve</button>
                            </form>
                            <button type="button" class="btn-red-soft" style="padding:6px 10px;font-size:12px;" onclick="promptReject({{ $req->id }})">Reject</button>
                            <form id="reject-form-{{ $req->id }}" method="POST" action="{{ route('hrd.leave-approval.process', $req) }}" style="display:none;">
                                @csrf
                                <input type="hidden" name="action" value="ditolak">
                                <input type="hidden" name="rejection_reason" class="rejection-reason-input">
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">
                        <div class="empty-state" style="padding:40px;">
                            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                            <div class="empty-state-title">Tidak ada pengajuan HR</div>
                            <div class="empty-state-desc">Semua pengajuan cuti saat ini ditangani oleh atasan atau belum ada atasan cuti.</div>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($requests->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $requests->withQueryString()->links() }}
    </div>
@endif
@endsection

@section('scripts')
<script>
    function promptReject(id) {
        const reason = prompt('Masukkan alasan penolakan:');
        if (!reason) return;
        const form = document.getElementById('reject-form-' + id);
        form.querySelector('.rejection-reason-input').value = reason;
        form.style.display = 'inline-flex';
        form.submit();
    }
</script>
@endsection
