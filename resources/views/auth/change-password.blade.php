<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — E-Leave</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f2554 0%, #1a56db 60%, #3b82f6 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .cp-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .cp-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: #d97706;
            margin: 0 auto 20px;
            box-shadow: 0 4px 16px rgba(217,119,6,.2);
        }
        h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; color: #0f2554; }
        .form-label { font-size: 13px; font-weight: 700; color: #374151; }
        .form-control {
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            padding: 10px 13px; font-family: 'Nunito', sans-serif;
        }
        .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 3px rgba(26,86,219,.12); outline: none; }
        .btn-save {
            width: 100%;
            background: linear-gradient(135deg, #0f2554, #1a56db);
            color: #fff; border: none; border-radius: 10px;
            padding: 12px; font-weight: 700; font-size: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
            box-shadow: 0 4px 14px rgba(26,86,219,.35);
        }
        .rules-box {
            background: #f0f4ff; border: 1.5px solid #dbeafe;
            border-radius: 10px; padding: 12px 16px; margin-top: 12px;
        }
        .rule-item { font-size: 12px; color: #475569; display: flex; align-items: center; gap: 7px; padding: 3px 0; }
        .rule-item i { font-size: 11px; }
        .rule-ok   { color: #16a34a; }
        .rule-fail { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="cp-card">
        <div class="cp-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h4 class="text-center mb-1">Ganti Password</h4>
        <p class="text-center text-secondary mb-4" style="font-size:13.5px;">
            Demi keamanan akun, Anda wajib mengganti password default sebelum melanjutkan.
        </p>

        @if($errors->any())
            <div style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:11px 15px;font-size:13px;display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="bi bi-exclamation-circle-fill"></i>{{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.change-password.post') }}" id="cpForm">
            @csrf

            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" id="newPass"
                       class="form-control"
                       placeholder="Minimal 8 karakter"
                       oninput="checkRules(this.value)">
            </div>

            <div class="mb-4">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation"
                       class="form-control"
                       placeholder="Ulangi password baru">
            </div>

            {{-- Aturan password --}}
            <div class="rules-box">
                <div style="font-size:11px;font-weight:700;color:#1a56db;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                    Syarat Password
                </div>
                <div class="rule-item rule-fail" id="r1"><i class="bi bi-circle"></i> Minimal 8 karakter</div>
                <div class="rule-item rule-fail" id="r2"><i class="bi bi-circle"></i> Mengandung huruf besar</div>
                <div class="rule-item rule-fail" id="r3"><i class="bi bi-circle"></i> Mengandung angka</div>
            </div>

            <button type="submit" class="btn-save mt-4">
                <i class="bi bi-check-circle-fill me-2"></i>Simpan Password Baru
            </button>
        </form>
    </div>

    <script>
        function checkRules(val) {
            setRule('r1', val.length >= 8);
            setRule('r2', /[A-Z]/.test(val));
            setRule('r3', /[0-9]/.test(val));
        }
        function setRule(id, ok) {
            const el = document.getElementById(id);
            el.className = 'rule-item ' + (ok ? 'rule-ok' : 'rule-fail');
            el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        }
    </script>
</body>
</html>