@extends('layouts.app')

@section('title', 'Detail Cuti')
@section('page-title', 'Detail Pengajuan Cuti')
@section('breadcrumb', 'Cuti › Detail & Tracking')

@section('content')
<div class="row g-4">

    {{-- Kiri: Detail pengajuan --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Informasi Pengajuan</span>
                <span class="status-badge status-{{ $leaveRequest->status }}">
                    {{ ucfirst($leaveRequest->status) }}
                </span>
            </div>
            <div class="card-body p-0">

                @php
                    $rows = [
                        ['icon'=>'bi-person-fill',         'label'=>'Pengaju',        'value'=>$leaveRequest->user->full_name . ' (' . $leaveRequest->user->nik . ')'],
                        ['icon'=>'bi-tag-fill',             'label'=>'Jenis Cuti',     'value'=>$leaveRequest->leaveType->name],
                        ['icon'=>'bi-calendar3',            'label'=>'Tanggal Ajukan', 'value'=>$leaveRequest->request_date->format('d F Y')],
                        ['icon'=>'bi-calendar-check',       'label'=>'Mulai Cuti',     'value'=>$leaveRequest->start_date->format('d F Y')],
                        ['icon'=>'bi-calendar-x',           'label'=>'Selesai Cuti',   'value'=>$leaveRequest->end_date->format('d F Y')],
                        ['icon'=>'bi-clock-fill',           'label'=>'Total Hari',     'value'=>$leaveRequest->total_days . ' hari kerja'],
                        ['icon'=>'bi-chat-text-fill',       'label'=>'Alasan',         'value'=>$leaveRequest->reason],
                    ];
                    if($leaveRequest->notes) $rows[] = ['icon'=>'bi-sticky-fill','label'=>'Catatan','value'=>$leaveRequest->notes];
                    if($leaveRequest->approver) {
                        $rows[] = ['icon'=>'bi-person-check-fill','label'=>'Diproses oleh','value'=>$leaveRequest->approver->full_name];
                        $rows[] = ['icon'=>'bi-clock-history','label'=>'Waktu Proses','value'=>$leaveRequest->approved_at?->format('d F Y, H:i')];
                    }
                    if($leaveRequest->isRejected() && $leaveRequest->rejection_reason) {
                        $rows[] = ['icon'=>'bi-x-circle-fill','label'=>'Alasan Penolakan','value'=>$leaveRequest->rejection_reason];
                    }
                @endphp

                @foreach($rows as $row)
                    <div style="display:flex;align-items:flex-start;gap:14px;padding:13px 20px;border-bottom:1px solid #f1f5f9;">
                        <div style="width:32px;height:32px;background:#f0f4ff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#1a56db;flex-shrink:0;">
                            <i class="bi {{ $row['icon'] }}"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">{{ $row['label'] }}</div>
                            <div style="font-size:13.5px;color:#1e293b;font-weight:600;margin-top:2px;">{{ $row['value'] }}</div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- Tombol aksi untuk Atasan --}}
        @if(Auth::user()->isAtasan() && $leaveRequest->isPending())
            <div class="card mt-4">
                <div class="card-header"><i class="bi bi-check2-square me-2 text-primary"></i>Tindakan Persetujuan</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('leave.approve', $leaveRequest) }}" id="approveForm">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Catatan Penolakan <span style="color:#94a3b8;font-weight:400;">(wajib jika menolak)</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="2"
                                      id="rejectionReason"
                                      placeholder="Tuliskan alasan penolakan..."></textarea>
                        </div>

                        <div style="display:flex;gap:10px;">
                            <button type="button" class="btn-green-soft" style="flex:1;justify-content:center;padding:10px;"
                                    onclick="submitApproval('disetujui')">
                                <i class="bi bi-check-circle-fill"></i> Setujui
                            </button>
                            <button type="button" class="btn-red-soft" style="flex:1;justify-content:center;padding:10px;"
                                    onclick="submitApproval('ditolak')">
                                <i class="bi bi-x-circle-fill"></i> Tolak
                            </button>
                        </div>

                        <input type="hidden" name="action" id="actionInput">
                    </form>
                </div>
            </div>
        @endif

        <div class="mt-3">
            @if(Auth::user()->isKaryawan())
                <a href="{{ route('leave.my-requests') }}" class="btn-blue-soft">
                    <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
                </a>
            @elseif(Auth::user()->isAtasan())
                <a href="{{ route('leave.approval-list') }}" class="btn-blue-soft">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Approval
                </a>
            @else
                <a href="{{ route('hrd.leave-report') }}" class="btn-blue-soft">
                    <i class="bi bi-arrow-left"></i> Kembali ke Laporan
                </a>
            @endif
        </div>
    </div>

    {{-- Kanan: Timeline tracking --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Tracking Status</div>
            <div class="card-body pt-4">
                <div class="timeline">

                    {{-- Step 1: Diajukan --}}
                    <div class="timeline-item">
                        <div class="timeline-dot done"></div>
                        <div class="timeline-title">Pengajuan Dikirim</div>
                        <div class="timeline-time">
                            {{ $leaveRequest->request_date->format('d M Y') }}
                            oleh {{ $leaveRequest->user->full_name }}
                        </div>
                        <div style="margin-top:6px;background:#f0f4ff;border-radius:8px;padding:8px 12px;font-size:12px;color:#475569;">
                            {{ $leaveRequest->leaveType->name }} · {{ $leaveRequest->total_days }} hari kerja
                        </div>
                    </div>

                    {{-- Step 2: Menunggu / Diproses --}}
                    <div class="timeline-item">
                        <div class="timeline-dot {{ $leaveRequest->isPending() ? 'wait' : 'done' }}"></div>
                        <div class="timeline-title">
                            @if($leaveRequest->isPending())
                                Menunggu Persetujuan Atasan
                            @else
                                Diproses oleh Atasan
                            @endif
                        </div>
                        <div class="timeline-time">
                            @if($leaveRequest->isPending())
                                Diteruskan ke {{ $leaveRequest->user->supervisor?->full_name ?? 'Atasan' }}
                            @else
                                {{ $leaveRequest->approved_at?->format('d M Y, H:i') }}
                                oleh {{ $leaveRequest->approver?->full_name }}
                            @endif
                        </div>
                    </div>

                    {{-- Step 3: Hasil --}}
                    <div class="timeline-item">
                        <div class="timeline-dot {{ $leaveRequest->isPending() ? 'empty' : ($leaveRequest->isApproved() ? 'done' : 'wait') }}"></div>
                        <div class="timeline-title">
                            @if($leaveRequest->isPending())
                                Keputusan Belum Ada
                            @elseif($leaveRequest->isApproved())
                                ✅ Pengajuan Disetujui
                            @else
                                ❌ Pengajuan Ditolak
                            @endif
                        </div>
                        <div class="timeline-time">
                            @if(!$leaveRequest->isPending())
                                {{ $leaveRequest->approved_at?->format('d M Y, H:i') }}
                                @if($leaveRequest->isRejected() && $leaveRequest->rejection_reason)
                                    <div style="margin-top:5px;background:#fee2e2;border-radius:7px;padding:7px 10px;font-size:12px;color:#b91c1c;">
                                        {{ $leaveRequest->rejection_reason }}
                                    </div>
                                @endif
                            @else
                                Menunggu tindakan atasan
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Info karyawan --}}
        <div class="card mt-4">
            <div class="card-header"><i class="bi bi-person-fill me-2 text-primary"></i>Info Karyawan</div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div class="avatar avatar-blue" style="width:44px;height:44px;font-size:17px;border-radius:12px;">
                        {{ strtoupper(substr($leaveRequest->user->full_name, 0, 2)) }}
                    </div>
                    <div>
                        <div style="font-weight:700;color:#0f2554;">{{ $leaveRequest->user->full_name }}</div>
                        <div style="font-size:12px;color:#64748b;">{{ $leaveRequest->user->position }} · NIK {{ $leaveRequest->user->nik }}</div>
                    </div>
                </div>
                <div style="font-size:12.5px;color:#64748b;display:flex;flex-direction:column;gap:5px;">
                    <div><i class="bi bi-building me-2"></i>{{ $leaveRequest->user->department?->name ?? '-' }}</div>
                    <div><i class="bi bi-envelope me-2"></i>{{ $leaveRequest->user->email }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function submitApproval(action) {
        const reason = document.getElementById('rejectionReason').value;
        if (action === 'ditolak' && !reason.trim()) {
            alert('Masukkan alasan penolakan terlebih dahulu.');
            document.getElementById('rejectionReason').focus();
            return;
        }
        if (confirm(action === 'disetujui' ? 'Setujui pengajuan cuti ini?' : 'Tolak pengajuan cuti ini?')) {
            document.getElementById('actionInput').value = action;
            document.getElementById('approveForm').submit();
        }
    }
</script>
@endsection