@extends('layouts.app')
@section('title', 'Daftar Karyawan')
@section('page-title', 'Daftar Karyawan')
@section('breadcrumb', 'HRD › Karyawan')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div style="font-size:13.5px;color:#64748b;">Kelola data karyawan dan akun dalam sistem</div>
    <a href="{{ route('hrd.users.create') }}" class="btn-blue" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="bi bi-person-plus-fill"></i> Tambah Karyawan
    </a>
</div>

@if(session('success'))
    <div class="flash-alert flash-success mb-3">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="flash-alert flash-danger mb-3">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

<div class="card">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departemen</th>
                        <th>Atasan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->nik }}</td>
                            <td>{{ $user->full_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td>{{ $user->department?->name ?? '-' }}</td>
                            <td>{{ $user->supervisor?->full_name ?? '-' }}</td>
                            <td>
                                @if($user->role === 'atasan')
                                    <span style="padding:6px 12px;border-radius:999px;font-size:13px;letter-spacing:.25px;background:{{ $user->status_aktif === 'cuti' ? '#fee2e2' : '#dbf3d9' }};color:{{ $user->status_aktif === 'cuti' ? '#991b1b' : '#166534' }};">
                                        {{ ucfirst($user->status_aktif) }}
                                    </span>
                                @else
                                    <span style="padding:6px 12px;border-radius:999px;font-size:13px;letter-spacing:.25px;background:{{ $user->is_active ? '#dbf3d9' : '#fee2e2' }};color:{{ $user->is_active ? '#166534' : '#991b1b' }};">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end" style="min-width:240px;">
                                <div style="display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap;">
                                    <a href="{{ route('hrd.users.edit', $user) }}" class="btn-blue-soft" style="padding:6px 10px;font-size:12px;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="POST" action="{{ route('hrd.users.reset-password', $user) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-yellow-soft" style="padding:6px 10px;font-size:12px;">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('hrd.users.toggle-active', $user) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-purple-soft" style="padding:6px 10px;font-size:12px;">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('hrd.users.destroy', $user) }}" style="display:inline;" onsubmit="return confirm('Hapus karyawan {{ $user->full_name }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-red-soft" style="padding:6px 10px;font-size:12px;">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                Belum ada karyawan terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->withQueryString()->links() }}</div>
    </div>
</div>
@endsection