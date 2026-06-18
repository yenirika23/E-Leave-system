<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Tampilkan halaman login
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // Proses login
    public function login(Request $request)
    {
        $request->validate([
            'nik'      => 'required|string',
            'password' => 'required|string',
        ], [
            'nik.required'      => 'NIK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Cari user berdasarkan NIK (bukan email!)
        $user = User::where('nik', $request->nik)->first();

        // Cek apakah user ada dan password cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['nik' => 'NIK atau password salah.'])
                         ->withInput(['nik' => $request->nik]);
        }

        // Cek apakah akun aktif
        if (!$user->is_active) {
            return back()->withErrors(['nik' => 'Akun Anda tidak aktif. Hubungi HRD.']);
        }

        // Login berhasil — buat session
        Auth::login($user, $request->boolean('remember'));

        // Jika harus ganti password (pertama kali login)
        if ($user->must_change_password) {
            return redirect()->route('auth.change-password')
                             ->with('info', 'Anda harus mengganti password sebelum melanjutkan.');
        }

        return redirect()->route('dashboard')
                         ->with('success', 'Selamat datang, ' . $user->full_name . '!');
    }

    // Tampilkan form ganti password
    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    // Proses ganti password
    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password'              => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ], [
            'new_password.required'  => 'Password baru wajib diisi.',
            'new_password.min'       => 'Password minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Update password dan tandai tidak perlu ganti lagi
        Auth::user()->update([
            'password'             => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
                         ->with('success', 'Password berhasil diganti! Selamat datang.');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }
}