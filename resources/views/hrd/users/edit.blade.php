@extends('layouts.app')
@section('title', 'Edit Karyawan')
@section('page-title', 'Edit Karyawan')
@section('breadcrumb', 'HRD › Karyawan › Edit')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil-fill me-2 text-primary"></i>Edit Karyawan: {{ $user->full_name }}</div>
    <div class="card-body p-4">

        @if($errors->any())
            <div class="flash-alert flash-danger mb-3">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        @endif

        <form method="POST" action="{{ route('hrd.users.update', $user) }}">
            @csrf @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">NIK <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="nik" class="form-control" value="{{ old('nik', $user->nik) }}" required maxlength="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}" required maxlength="100">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin <span style="color:#ef4444;">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="L" {{ old('gender', $user->gender) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('gender', $user->gender) === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Masuk</label>
                    <input type="date" name="join_date" class="form-control" value="{{ old('join_date', optional($user->join_date)->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Posisi</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Departemen</label>
                    <select name="department_id" class="form-select">
                        <option value="">Pilih departemen</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Role <span style="color:#ef4444;">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="">Pilih role</option>
                        <option value="hrd" {{ old('role', $user->role) === 'hrd' ? 'selected' : '' }}>HRD</option>
                        <option value="atasan" {{ old('role', $user->role) === 'atasan' ? 'selected' : '' }}>Atasan</option>
                        <option value="karyawan" {{ old('role', $user->role) === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Atasan</label>
                    <select name="supervisor_id" class="form-select">
                        <option value="">Pilih atasan</option>
                        @foreach($atasan as $supervisor)
                            <option value="{{ $supervisor->id }}" {{ old('supervisor_id', $user->supervisor_id) == $supervisor->id ? 'selected' : '' }}>
                                {{ $supervisor->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Status Atasan</label>
                    <select name="status_aktif" class="form-select">
                        <option value="aktif" {{ old('status_aktif', $user->status_aktif) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="cuti" {{ old('status_aktif', $user->status_aktif) === 'cuti' ? 'selected' : '' }}>Cuti</option>
                    </select>
                    <div class="form-text">Ubah status atasan ke cuti atau aktif. Ini hanya digunakan jika role adalah Atasan.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status Akun</label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ !old('is_active', $user->is_active) ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn-blue" style="flex:1;">
                    <i class="bi bi-save-fill me-2"></i>Simpan Perubahan
                </button>
                <a href="{{ route('hrd.users.index') }}" class="btn-blue-soft" style="padding:11px 18px;display:inline-flex;align-items:center;">
                    Batal
                </a>
            </div>
        </form>

        <div class="mt-4" style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('hrd.users.reset-password', $user) }}">
                @csrf
                <button type="submit" class="btn-yellow-soft" style="padding:10px 16px;">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Password ke NIK
                </button>
            </form>
            <form method="POST" action="{{ route('hrd.users.toggle-active', $user) }}">
                @csrf
                <button type="submit" class="btn-purple-soft" style="padding:10px 16px;">
                    {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                </button>
            </form>
        </div>
    </div>
</div>
</div></div>
@endsection