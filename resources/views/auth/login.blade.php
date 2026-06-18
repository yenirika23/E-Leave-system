<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — E-Leave</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --navy: #0f2554; --blue: #1a56db; }

        * { box-sizing: border-box; }
        body {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            background: #f0f4ff;
        }

        /* Panel kiri — dekorasi biru */
        .login-left {
            flex: 1;
            background: linear-gradient(140deg, var(--navy) 0%, #1a56db 60%, #3b82f6 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        /* Lingkaran dekorasi */
        .login-left::before {
            content: '';
            position: absolute; top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .login-left::after {
            content: '';
            position: absolute; bottom: -80px; left: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
        }

        .left-logo {
            width: 80px; height: 80px;
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; color: #fff;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
            position: relative; z-index: 1;
        }

        .left-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 42px; font-weight: 800;
            color: #fff;
            line-height: 1.1;
            position: relative; z-index: 1;
        }
        .left-title span { color: #93c5fd; }
        .left-sub {
            color: rgba(255,255,255,.65);
            font-size: 15px;
            margin-top: 12px;
            max-width: 300px;
            text-align: center;
            line-height: 1.6;
            position: relative; z-index: 1;
        }

        /* Fitur highlight */
        .left-features {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative; z-index: 1;
            width: 100%;
            max-width: 320px;
        }
        .feat-item {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,.9);
        }
        .feat-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: #93c5fd;
            flex-shrink: 0;
        }
        .feat-text { font-size: 13px; font-weight: 500; }

        /* Panel kanan — form */
        .login-right {
            width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: #fff;
        }

        .login-form-wrap { width: 100%; max-width: 380px; }

        .form-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 26px; font-weight: 800;
            color: var(--navy);
            margin-bottom: 4px;
        }
        .form-desc { font-size: 14px; color: #64748b; margin-bottom: 32px; }

        .form-label { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px; }

        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            font-size: 16px; color: #94a3b8;
        }
        .login-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px 10px 40px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            transition: all .15s;
            outline: none;
        }
        .login-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
        }
        .login-input.is-invalid { border-color: #ef4444; }

        .toggle-pass {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer;
            font-size: 16px; padding: 2px;
        }
        .toggle-pass:hover { color: var(--blue); }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--navy) 0%, var(--blue) 100%);
            color: #fff; border: none;
            border-radius: 10px; padding: 12px;
            font-size: 15px; font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 4px 16px rgba(26,86,219,.35);
            margin-top: 8px;
        }
        .btn-login:hover {
            box-shadow: 0 6px 24px rgba(26,86,219,.5);
            transform: translateY(-1px);
        }

        .alert-err {
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 10px;
            padding: 11px 15px;
            font-size: 13px;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px;
        }

        .hint-box {
            background: #f0f4ff;
            border: 1.5px solid #dbeafe;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 24px;
        }
        .hint-title { font-size: 11px; font-weight: 700; color: var(--blue); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .hint-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #475569; padding: 3px 0; border-bottom: 1px dashed #e2e8f0; }
        .hint-row:last-child { border-bottom: none; }
        .hint-badge { font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px; }
        .hb-hrd { background: #dbeafe; color: var(--blue); }
        .hb-atasan { background: #dcfce7; color: #15803d; }
        .hb-kary { background: #f3e8ff; color: #7c3aed; }

        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { width: 100%; }
        }
    </style>
</head>
<body>

    {{-- Panel Kiri --}}
    <div class="login-left">
        <div class="left-logo"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="left-title text-center">E-<span>Leave</span><br>System</div>
        <div class="left-sub">Sistem Informasi Pengelolaan Cuti Karyawan Berbasis Web</div>

        <div class="left-features">
            <div class="feat-item">
                <div class="feat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <div class="feat-text">Pengajuan cuti cepat & mudah secara online</div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="bi bi-bell-fill"></i></div>
                <div class="feat-text">Notifikasi real-time ke atasan & karyawan</div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><i class="bi bi-shield-check-fill"></i></div>
                <div class="feat-text">Hak akses terpisah: HRD, Atasan, Karyawan</div>
            </div>
        </div>
    </div>

    {{-- Panel Kanan (Form) --}}
    <div class="login-right">
        <div class="login-form-wrap">
            <div class="form-title">Selamat Datang 👋</div>
            <div class="form-desc">Masuk menggunakan NIK dan password Anda</div>

            {{-- Error --}}
            @if($errors->any())
                <div class="alert-err">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert-err" style="background:#dcfce7;color:#15803d;">
                    <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                {{-- NIK --}}
                <div class="mb-4">
                    <label class="form-label">NIK (Nomor Induk Karyawan)</label>
                    <div class="input-wrap">
                        <i class="bi bi-person-badge input-icon"></i>
                        <input type="text" name="nik"
                               class="login-input {{ $errors->has('nik') ? 'is-invalid' : '' }}"
                               placeholder="Masukkan NIK Anda"
                               value="{{ old('nik') }}"
                               autocomplete="username"
                               autofocus>
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="password" id="passInput"
                               class="login-input"
                               placeholder="Masukkan password"
                               autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="togglePass()">
                            <i class="bi bi-eye" id="eyeBtn"></i>
                        </button>
                    </div>
                    <div style="font-size:11.5px;color:#94a3b8;margin-top:5px;">
                        <i class="bi bi-info-circle me-1"></i>Password default = NIK Anda
                    </div>
                </div>

                {{-- Remember --}}
                <div class="mb-1" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="rem" name="remember"
                           style="width:15px;height:15px;accent-color:var(--blue);cursor:pointer;">
                    <label for="rem" style="font-size:13px;color:#64748b;cursor:pointer;">Ingat saya</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
                </button>
            </form>

            <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:24px;">
                &copy; {{ date('Y') }} E-Leave System — PT. Semikonduktor
            </p>
        </div>
    </div>

    <script>
        function togglePass() {
            const inp = document.getElementById('passInput');
            const ico = document.getElementById('eyeBtn');
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.className = 'bi bi-eye-slash';
            } else {
                inp.type = 'password';
                ico.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>