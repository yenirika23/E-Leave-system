@extends('layouts.app')

@section('title', 'Ajukan Cuti')
@section('page-title', 'Ajukan Cuti')
@section('breadcrumb', 'Pengajuan Cuti')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Kuota singkat --}}
        @php
            $quotas = Auth::user()
                ->leaveQuotas()
                ->with('leaveType')
                ->where('year', now()->year)
                ->get();
        @endphp

        <div class="row g-2 mb-4">
            @foreach($quotas as $q)
                <div class="col-6 col-md-4 col-lg-3">
                    <div style="background:#fff;border:1.5px solid #e8edf5;border-radius:10px;padding:10px 13px;text-align:center;">

                        @if($q->isUnlimited())
                            <div style="font-size:20px;font-weight:800;color:#7c3aed;">
                                ∞
                            </div>
                        @else
                            <div style="font-size:20px;font-weight:800;color:#0f2554;">
                                {{ $q->remaining_quota }}
                            </div>
                        @endif

                        <div style="font-size:10.5px;color:#64748b;font-weight:600;">
                            {{ $q->leaveType->code }}
                        </div>

                        <div style="font-size:10px;color:#94a3b8;">
                            {{ $q->leaveType->unit ?? 'hari' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header">
                <span>
                    <i class="bi bi-plus-circle-fill me-2 text-primary"></i>
                    Form Pengajuan Cuti
                </span>
            </div>

            <div class="card-body p-4">

                {{-- Error --}}
                @if($errors->any())
                    <div class="flash-alert flash-danger mb-3">
                        <i class="bi bi-exclamation-circle-fill"></i>

                        <div>
                            @foreach($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('leave.store') }}"
                      enctype="multipart/form-data"
                      id="leaveForm">

                    @csrf

                    {{-- Jenis Cuti --}}
                    <div class="mb-4">
                        <label class="form-label">
                            Jenis Cuti
                            <span style="color:#ef4444;">*</span>
                        </label>

                        <select name="leave_type_id"
                                class="form-select"
                                required
                                id="leaveTypeSelect">

                            <option value="">
                                -- Pilih jenis cuti --
                            </option>

                            @foreach($leaveTypes as $lt)
                                <option value="{{ $lt->id }}"
                                        data-quota="{{ Auth::user()->getRemainingQuota($lt->id) }}"
                                        data-unlimited="{{ $lt->is_unlimited ? '1' : '0' }}"
                                        data-halfday="{{ $lt->allow_half_day ? '1' : '0' }}"
                                        data-unit="{{ $lt->unit }}"
                                        data-code="{{ $lt->code }}"
                                        {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>

                                    {{ $lt->name }}

                                    @if($lt->is_unlimited)
                                        — (Tidak Terbatas)
                                    @else
                                        — (Sisa:
                                        {{ Auth::user()->getRemainingQuota($lt->id) }}
                                        {{ $lt->unit }})
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        {{-- Info quota --}}
                        <div id="quotaInfo"
                             style="display:none;margin-top:8px;background:#f0f4ff;border:1.5px solid #dbeafe;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#1a56db;">

                            <i class="bi bi-info-circle me-1"></i>

                            Sisa kuota Anda:
                            <b id="quotaNum">0</b>
                            <span id="quotaUnit">hari</span>
                        </div>

                        {{-- Unlimited --}}
                        <div id="unlimitedInfo"
                             style="display:none;margin-top:8px;background:#f3e8ff;border:1.5px solid #e9d5ff;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#7c3aed;">

                            <i class="bi bi-infinity me-1"></i>

                            Jenis cuti ini
                            <b>tidak terbatas</b>
                            — tidak ada batasan jumlah hari
                        </div>

                        {{-- Cuti sakit --}}
                        <div id="sickInfo"
                             style="display:none;margin-top:8px;background:#fef9c3;border:1.5px solid #fde68a;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#854d0e;">

                            <i class="bi bi-exclamation-triangle me-1"></i>

                            Cuti Sakit
                            <b>wajib melampirkan surat dokter</b>
                        </div>
                    </div>

                    {{-- Tipe Hari --}}
                    <div class="mb-4"
                         id="dayTypeSection"
                         style="display:none;">

                        <label class="form-label">
                            Tipe Hari
                            <span style="color:#ef4444;">*</span>
                        </label>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;">

                            {{-- Full Day --}}
                            <label style="flex:1;min-width:120px;cursor:pointer;" id="labelFull">
                                <input type="radio"
                                       name="day_type"
                                       value="full"
                                       id="dtFull"
                                       style="display:none;"
                                       {{ old('day_type', 'full') === 'full' ? 'checked' : '' }}>

                                <div class="day-opt" id="optFull">
                                    <div style="font-size:20px;">📅</div>

                                    <div style="font-weight:700;font-size:13px;margin-top:4px;">
                                        Full Day
                                    </div>

                                    <div style="font-size:11px;color:#64748b;">
                                        Satu hari penuh
                                    </div>
                                </div>
                            </label>

                            {{-- Morning --}}
                            <label style="flex:1;min-width:120px;cursor:pointer;" id="labelMorning">
                                <input type="radio"
                                       name="day_type"
                                       value="morning"
                                       id="dtMorning"
                                       style="display:none;"
                                       {{ old('day_type') === 'morning' ? 'checked' : '' }}>

                                <div class="day-opt" id="optMorning">
                                    <div style="font-size:20px;">🌅</div>

                                    <div style="font-weight:700;font-size:13px;margin-top:4px;">
                                        Pagi
                                    </div>

                                    <div style="font-size:11px;color:#64748b;">
                                        08.00 – 12.00
                                    </div>
                                </div>
                            </label>

                            {{-- Afternoon --}}
                            <label style="flex:1;min-width:120px;cursor:pointer;" id="labelAfternoon">
                                <input type="radio"
                                       name="day_type"
                                       value="afternoon"
                                       id="dtAfternoon"
                                       style="display:none;"
                                       {{ old('day_type') === 'afternoon' ? 'checked' : '' }}>

                                <div class="day-opt" id="optAfternoon">
                                    <div style="font-size:20px;">🌤️</div>

                                    <div style="font-weight:700;font-size:13px;margin-top:4px;">
                                        Siang
                                    </div>

                                    <div style="font-size:11px;color:#64748b;">
                                        13.00 – 17.00
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Hidden default --}}
                    <div id="dayTypeHidden">
                        <input type="hidden"
                               name="day_type"
                               value="full"
                                id="hiddenDayType">
                    </div>

                    {{-- Tanggal --}}
                    <div class="row g-3 mb-4">

                        <div class="col-md-6">
                            <label class="form-label">
                                Tanggal Mulai
                                <span style="color:#ef4444;">*</span>
                            </label>

                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   value="{{ old('start_date') }}"
                                   min="{{ now()->format('Y-m-d') }}"
                                   required
                                   id="startDate">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Tanggal Selesai
                                <span style="color:#ef4444;">*</span>
                            </label>

                            <input type="date"
                                   name="end_date"
                                   class="form-control"
                                   value="{{ old('end_date') }}"
                                   min="{{ now()->format('Y-m-d') }}"
                                   required
                                   id="endDate">
                        </div>
                    </div>

                    {{-- Total hari --}}
                    <div id="dayCount"
                         style="display:none;margin-bottom:20px;background:#dcfce7;border:1.5px solid #bbf7d0;border-radius:9px;padding:10px 14px;font-size:13.5px;color:#15803d;align-items:center;gap:8px;">

                        <i class="bi bi-calendar-check-fill"></i>

                        Total:
                        <b id="dayNum">0</b>
                        <span id="dayText">hari kerja</span>
                    </div>

                    {{-- Upload dokumen --}}
                    <div class="mb-4">
                        <label class="form-label">
                            Lampiran Dokumen
                            <span style="color:#94a3b8;font-weight:400;">(opsional)</span>
                        </label>

                        <input type="file"
                               name="document"
                               class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png">

                        <div style="font-size:11.5px;color:#94a3b8;margin-top:4px;">
                            Format: PDF, JPG, JPEG, PNG (maks. 2MB)
                        </div>
                    </div>

                    {{-- Alasan --}}
                    <div class="mb-4">
                        <label class="form-label">
                            Alasan Cuti
                            <span style="color:#ef4444;">*</span>
                        </label>

                        <textarea name="reason"
                                  class="form-control"
                                  rows="3"
                                  required
                                  minlength="10"
                                  placeholder="Jelaskan alasan pengajuan cuti Anda...">{{ old('reason') }}</textarea>

                        <div style="font-size:11.5px;color:#94a3b8;margin-top:4px;">
                            Minimal 10 karakter
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-4">
                        <label class="form-label">
                            Catatan Tambahan
                            <span style="color:#94a3b8;font-weight:400;">(opsional)</span>
                        </label>

                        <textarea name="notes"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Catatan tambahan jika ada...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Info atasan --}}
                    @if(Auth::user()->supervisor)
                        <div style="background:#f0f4ff;border:1.5px solid #dbeafe;border-radius:10px;padding:12px 16px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">

                            <div class="stat-icon blue"
                                 style="width:38px;height:38px;font-size:16px;flex-shrink:0;">
                                <i class="bi bi-person-check-fill"></i>
                            </div>

                            <div>
                                <div style="font-size:11px;color:#64748b;font-weight:700;">
                                    Pengajuan akan dikirim ke:
                                </div>

                                <div style="font-size:13.5px;font-weight:700;color:#0f2554;">
                                    {{ Auth::user()->supervisor->full_name }}
                                </div>

                                <div style="font-size:11.5px;color:#64748b;">
                                    {{ Auth::user()->supervisor->position }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flash-alert flash-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Anda belum memiliki atasan. Hubungi HRD.
                        </div>
                    @endif

                    {{-- Button --}}
                    <div style="display:flex;gap:10px;">

                        <button type="submit"
                                class="btn-blue"
                                style="flex:1;padding:11px;">

                            <i class="bi bi-send-fill me-2"></i>
                            Kirim Pengajuan
                        </button>

                        <a href="{{ route('leave.my-requests') }}"
                           class="btn-blue-soft"
                           style="padding:11px 18px;">
                            Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.day-opt {
    border: 1.5px solid #dbe3f0;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    background: #fff;
    transition: .2s;
}
.day-opt:hover {
    border-color: #3b82f6;
    transform: translateY(-1px);
}
.day-opt.active {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}
</style>

<script>
const leaveTypeSelect = document.getElementById('leaveTypeSelect');

leaveTypeSelect.addEventListener('change', function () {
    const opt       = this.options[this.selectedIndex];
    const quota     = opt.dataset.quota;
    const unlimited = opt.dataset.unlimited;
    const halfday   = opt.dataset.halfday;
    const unit      = opt.dataset.unit || 'hari';
    const code      = opt.dataset.code || '';

    const dayTypeSection = document.getElementById('dayTypeSection');
    const hiddenBox      = document.getElementById('dayTypeHidden');
    const endDateInput   = document.getElementById('endDate');

    // Reset info boxes
    document.getElementById('quotaInfo').style.display     = 'none';
    document.getElementById('unlimitedInfo').style.display = 'none';
    document.getElementById('sickInfo').style.display      = 'none';

    if (this.value) {
        if (unlimited === '1') {
            document.getElementById('unlimitedInfo').style.display = 'block';
        } else {
            document.getElementById('quotaNum').textContent  = quota;
            document.getElementById('quotaUnit').textContent = unit;
            document.getElementById('quotaInfo').style.display = 'block';
        }
    }

    if (code === 'CS') {
        document.getElementById('sickInfo').style.display = 'block';
    }

    // -------------------------------------------------------
    // Logika tampilan tipe hari berdasarkan kode cuti
    // -------------------------------------------------------

    if (code === 'UPLF') {
        // UPL Full Day: tampilkan section, hanya Full Day
        dayTypeSection.style.display = 'block';
        hiddenBox.style.display      = 'none';
        document.getElementById('labelFull').style.display      = 'block';
        document.getElementById('labelMorning').style.display   = 'none';
        document.getElementById('labelAfternoon').style.display = 'none';
        document.getElementById('dtFull').checked = true;
        endDateInput.removeAttribute('readonly');
        endDateInput.style.pointerEvents = '';
        endDateInput.style.background = '';

    } else if (code === 'UPLH') {
        // UPL Half Day: tampilkan section, hanya Pagi & Siang
        dayTypeSection.style.display = 'block';
        hiddenBox.style.display      = 'none';
        document.getElementById('labelFull').style.display      = 'none';
        document.getElementById('labelMorning').style.display   = 'block';
        document.getElementById('labelAfternoon').style.display = 'block';
        // Default ke morning jika full sedang terpilih
        if (document.getElementById('dtFull').checked) {
            document.getElementById('dtMorning').checked = true;
        }
        // End date dikunci = start date
        endDateInput.setAttribute('readonly', true);
        endDateInput.style.pointerEvents = 'none';
        endDateInput.style.background = '#f8fafc';
        const startVal = document.getElementById('startDate').value;
        if (startVal) endDateInput.value = startVal;

    } else if (halfday === '1') {
        // CT atau jenis lain yang allow_half_day: tampilkan semua opsi
        dayTypeSection.style.display = 'block';
        hiddenBox.style.display      = 'none';
        document.getElementById('labelFull').style.display      = 'block';
        document.getElementById('labelMorning').style.display   = 'block';
        document.getElementById('labelAfternoon').style.display = 'block';
        endDateInput.removeAttribute('readonly');
        endDateInput.style.pointerEvents = '';
        endDateInput.style.background = '';
        // Jika sebelumnya dikunci karena UPLH, reset ke full
        if (!document.getElementById('dtFull').checked &&
            !document.getElementById('dtMorning').checked &&
            !document.getElementById('dtAfternoon').checked) {
            document.getElementById('dtFull').checked = true;
        }

    } else {
        // Tidak support half day: sembunyikan section, kirim full
        dayTypeSection.style.display = 'none';
        hiddenBox.style.display      = 'block';
        document.getElementById('dtFull').checked = true;
        endDateInput.removeAttribute('readonly');
        endDateInput.style.pointerEvents = '';
        endDateInput.style.background = '';
    }

    updateDayType();
    calcDays();
});

function updateDayType() {
    ['Full', 'Morning', 'Afternoon'].forEach(t => {
        const el = document.getElementById('opt' + t);
        const rd = document.getElementById('dt' + t);
        if (el && rd) {
            el.classList.toggle('active', rd.checked);
        }
    });

    // Jika half day dipilih, end date = start date
    const selected = document.querySelector('input[name="day_type"]:checked');
    if (selected && selected.value !== 'full') {
        const startVal = document.getElementById('startDate').value;
        if (startVal) document.getElementById('endDate').value = startVal;
    }

    calcDays();
}

function calcDays() {
    const s   = document.getElementById('startDate').value;
    const e   = document.getElementById('endDate').value;
    const box = document.getElementById('dayCount');

    if (!s) { box.style.display = 'none'; return; }

    const selected = document.querySelector('input[name="day_type"]:checked');
    const isHalf   = selected && selected.value !== 'full';

    if (isHalf) {
        document.getElementById('dayNum').textContent  = '0.5';
        document.getElementById('dayText').textContent = 'hari';
        box.style.display = 'flex';
        return;
    }

    if (!e) { box.style.display = 'none'; return; }

    let days = 0;
    let cur  = new Date(s);
    let end  = new Date(e);

    if (end < cur) { box.style.display = 'none'; return; }

    while (cur <= end) {
        if (cur.getDay() !== 0 && cur.getDay() !== 6) days++;
        cur.setDate(cur.getDate() + 1);
    }

    document.getElementById('dayNum').textContent  = days;
    document.getElementById('dayText').textContent = 'hari kerja';
    box.style.display = 'flex';
}

document.getElementById('startDate').addEventListener('change', function () {
    const selected = document.querySelector('input[name="day_type"]:checked');
    if (selected && selected.value !== 'full') {
        document.getElementById('endDate').value = this.value;
    }
    calcDays();
});

document.getElementById('endDate').addEventListener('change', function () {
    const s = document.getElementById('startDate').value;
    if (s && this.value < s) this.value = s;
    calcDays();
});

// Attach event ke semua radio
document.querySelectorAll('input[name="day_type"]').forEach(el => {
    el.addEventListener('change', updateDayType);
});

window.addEventListener('load', () => {
    leaveTypeSelect.dispatchEvent(new Event('change'));
});
</script>
@endsection