<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MustChangePassword
{
    /**
     * Paksa user yang belum ganti password untuk ke halaman ganti password dulu.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            // Kalau sudah di halaman change-password atau logout, biarkan
            if ($request->routeIs('auth.change-password') ||
                $request->routeIs('auth.change-password.post') ||
                $request->routeIs('logout')) {
                return $next($request);
            }
            // Paksa ke halaman ganti password
            return redirect()->route('auth.change-password')
                             ->with('warning', 'Anda harus mengganti password terlebih dahulu.');
        }

        return $next($request);
    }
}