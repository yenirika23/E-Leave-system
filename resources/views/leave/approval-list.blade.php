@extends('layouts.app')

@section('title', 'Approval Cuti')
@section('page-title', 'Approval Cuti')
@section('breadcrumb', 'Atasan › Daftar Pengajuan Cuti')

@section('content')

{{-- Filter tab status --}}
@php
    $filterStatus = request('status', '');
    $statuses = ['' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
@endphp
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    @foreach($statuses as $val => $label)
        <a href="{{ route('leave.approval-list', $val ? ['status'=>$val] : []) }}"
           style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none;border:1.5px solid;
                  {{ $filterStatus === $val
                     ? 'background:#1a56db;color:#fff;border-color:#1a56db;'
                     : 'background:#fff;color:#64748b;border-color:#e2e8f0;' }}">
            {{ $label }}
            @if($val === 'menunggu')
                @php $c = \App\Models\LeaveRequest::whereHas('user', fn($q)=>$q->where('supervisor_id',Auth::id()))->where('status','menunggu')->count() @endphp
                @if($c > 0)<span style="background:#ef4444;color:#fff;border-radius:10px;font-size:10px;padding:1px 6px;margin-left:4px;">{{ $c }}</span>@endif
            @endif
        </a>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse($requests as $req)
            <div style="padding:15px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">

                <div class="avatar avatar-{{ ['blue','green','purple','orange'][$loop->index % 4] }}"
                     style="width:42px;height:42px;border-radius:10px;font-size:14px;">
                    {{ strtoupper(substr($req->user->full_name, 0, 2)) }}
                </div>

                <div style="flex:1;min-width:150px;">
                    <div style="font-weight:700;font-size:14px;color:#0f2554;">{{ $req->user->full_name }}</div>
                    <div style="font-size:12px;color:#64748b;">
                        {{ $req->leaveType->name }} ·
                        {{ $req->start_date->format('d M') }} – {{ $req->end_date->format('d M Y') }} ·
                        <b>{{ $req->total_days }} hari</b>
                    </div>
                    <div style="font-size:11.5px;color:#94a3b8;">Diajukan: {{ $req->request_date->format('d M Y') }}</div>
                </div>

                <div style="font-size:12px;color:#64748b;max-width:180px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                    {{ $req->reason }}
                </div>

                <span class="status-badge status-{{ $req->status }}">{{ ucfirst($req->status) }}</span>

                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="{{ route('leave.approval-show', $req) }}" class="btn-blue-soft" style="font-size:12px;padding:6px 12px;">
                        <i class="bi bi-eye"></i> Detail
                    </a>
                    @if($req->isPending())
                        <form method="POST" action="{{ route('leave.approve', $req) }}"
                              onsubmit="return confirm('Setujui pengajuan ini?')">
                            @csrf
                            <input type="hidden" name="action" value="disetujui">
                            <button type="submit" class="btn-green-soft" style="font-size:12px;padding:6px 12px;cursor:pointer;border:none;">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-clipboard-x"></i></div>
                <div class="empty-state-title">Tidak ada pengajuan</div>
                <div class="empty-state-desc">Belum ada pengajuan cuti dari tim Anda</div>
            </div>
        @endforelse
    </div>
</div>

@if($requests->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $requests->links() }}</div>
@endif
@endsection