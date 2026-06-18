@extends('layouts.app')

@section('title', 'Riwayat Cuti')
@section('page-title', 'Riwayat Cuti Saya')
@section('breadcrumb', 'Riwayat Cuti')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">

    <div style="font-size:13.5px;color:#64748b;">
        Semua riwayat pengajuan cuti Anda
    </div>

    <a href="{{ route('leave.create') }}"
       class="btn-blue"
       style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">

        <i class="bi bi-plus-circle-fill"></i>
        Ajukan Cuti Baru
    </a>

</div>

<div class="card">

    <div class="card-body p-0">

        @forelse($requests as $req)

            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">

                {{-- Nomor --}}
                <div style="width:28px;height:28px;background:#f0f4ff;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#1a56db;flex-shrink:0;">
                    {{ $loop->iteration }}
                </div>

                {{-- Icon --}}
                <div class="avatar avatar-blue">
                    <i class="bi bi-calendar2-week" style="font-size:16px;"></i>
                </div>

                {{-- Informasi Cuti --}}
                <div style="flex:1;min-width:160px;">

                    <div style="font-weight:700;font-size:14px;color:#0f2554;">
                        {{ $req->leaveType->name }}
                    </div>

                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                        <i class="bi bi-calendar3 me-1"></i>

                        {{ $req->start_date->format('d M Y') }}
                        —
                        {{ $req->end_date->format('d M Y') }}

                        &nbsp;·&nbsp;

                        <b>{{ $req->total_days }} hari</b>
                    </div>

                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">
                        Diajukan:
                        {{ $req->request_date->format('d M Y') }}
                    </div>

                </div>

                {{-- Alasan --}}
                <div style="flex:1;min-width:140px;max-width:220px;">

                    <div style="font-size:12px;color:#64748b;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                        {{ $req->reason }}
                    </div>

                    @if($req->isRejected() && $req->rejection_reason)

                        <div style="font-size:11.5px;color:#dc2626;margin-top:3px;">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ $req->rejection_reason }}
                        </div>

                    @endif

                </div>

                {{-- Status --}}
                <span class="status-badge status-{{ $req->status }}">

                    @if($req->status === 'menunggu')

                        <i class="bi bi-hourglass-split me-1"></i>

                    @elseif($req->status === 'disetujui')

                        <i class="bi bi-check-circle me-1"></i>

                    @elseif($req->status === 'dibatalkan')

                        <i class="bi bi-dash-circle me-1"></i>

                    @else

                        <i class="bi bi-x-circle me-1"></i>

                    @endif

                    {{ ucfirst($req->status) }}

                </span>

                {{-- Tombol Aksi --}}
                <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">

                    {{-- Detail --}}
                    <a href="{{ route('leave.show', $req) }}"
                       class="btn-blue-soft"
                       style="font-size:12px;padding:6px 12px;">

                        <i class="bi bi-eye"></i>
                        Detail
                    </a>

                    {{-- Batalkan --}}
                    @if($req->isPending())

                        <form method="POST"
                              action="{{ route('leave.cancel', $req) }}"
                              onsubmit="return confirm('Batalkan pengajuan cuti ini?\nStatus akan berubah menjadi Dibatalkan.')">

                            @csrf

                            <button type="submit"
                                    class="btn-yellow-soft"
                                    style="font-size:12px;padding:6px 12px;cursor:pointer;border:none;">

                                <i class="bi bi-x-circle"></i>
                                Batalkan
                            </button>

                        </form>

                    @endif

                    {{-- Hapus --}}
                    @if($req->isCancelled())

                        <form method="POST"
                              action="{{ route('leave.destroy', $req) }}"
                              onsubmit="return confirm('HAPUS permanen pengajuan ini?\nData tidak bisa dikembalikan!')">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="btn-red-soft"
                                    style="font-size:12px;padding:6px 12px;cursor:pointer;border:none;">

                                <i class="bi bi-trash3"></i>
                                Hapus
                            </button>

                        </form>

                    @endif

                </div>

            </div>

        @empty

            <div class="empty-state">

                <div class="empty-state-icon">
                    <i class="bi bi-inbox-fill"></i>
                </div>

                <div class="empty-state-title">
                    Belum ada pengajuan cuti
                </div>

                <div class="empty-state-desc">
                    Klik tombol di atas untuk membuat pengajuan pertama Anda
                </div>

                <a href="{{ route('leave.create') }}"
                   class="btn-blue mt-3"
                   style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">

                    <i class="bi bi-plus-circle-fill"></i>
                    Ajukan Cuti Sekarang
                </a>

            </div>

        @endforelse

    </div>

</div>

{{-- Pagination --}}
@if($requests->hasPages())

    <div class="d-flex justify-content-center mt-3">
        {{ $requests->links() }}
    </div>

@endif

@endsection