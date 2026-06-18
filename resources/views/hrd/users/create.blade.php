@extends('layouts.app')
@section('title', 'Tambah Karyawan')
@section('page-title', 'Tambah Karyawan')
@section('breadcrumb', 'HRD › Karyawan › Tambah')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah Karyawan Baru</div>
    <div class="card-body p-4">

        @if($errors->any())
            <div class="flash-alert flash-danger mb-3">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        @endif

        <form method="POST" action="{{ route('hrd.users.store') }}">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Masuk <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="join_date" class="form-control" value="{{ old('join_date') }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin <span style="color:#ef4444;">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="L" {{ old('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Posisi</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Departemen</label>
                    <select name="department_id" class="form-select">
                        <option value="">Pilih departemen</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                        <option value="hrd" {{ old('role') === 'hrd' ? 'selected' : '' }}>HRD</option>
                        <option value="atasan" {{ old('role') === 'atasan' ? 'selected' : '' }}>Atasan</option>
                        <option value="karyawan" {{ old('role') === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Atasan</label>
                    <select name="supervisor_id" class="form-select">
                        <option value="">Pilih atasan</option>
                        @foreach($atasan as $supervisor)
                            <option value="{{ $supervisor->id }}" {{ old('supervisor_id') == $supervisor->id ? 'selected' : '' }}>
                                {{ $supervisor->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status Atasan</label>
                    <select name="status_aktif" class="form-select">
                        <option value="aktif" {{ old('status_aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="cuti" {{ old('status_aktif') === 'cuti' ? 'selected' : '' }}>Cuti</option>
                    </select>
                    <div class="form-text">Hanya berlaku untuk role Atasan. Jika Atasan cuti, pengajuan bawahannya akan dialihkan ke HR.</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-blue" style="flex:1;">
                    <i class="bi bi-save-fill me-2"></i>Simpan Karyawan
                </button>
                <a href="{{ route('hrd.users.index') }}" class="btn-blue-soft" style="padding:11px 18px;display:inline-flex;align-items:center;">
                    Batal
                </a>
            </div>

            <div class="mt-3 text-muted" style="font-size:13px;">
                NIK akan dibuat otomatis berdasarkan tanggal masuk (format YYMMXX). Password default akan diatur ke NIK karyawan.
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection