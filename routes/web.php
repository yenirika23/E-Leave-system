<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\HrdController;

// ============================
// PUBLIK (tidak perlu login)
// ============================
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// ============================
// GANTI PASSWORD (harus login, boleh sebelum ganti password)
// ============================
Route::middleware('auth')->group(function () {
    Route::get('/ganti-password',  [AuthController::class, 'showChangePassword'])->name('auth.change-password');
    Route::post('/ganti-password', [AuthController::class, 'changePassword'])->name('auth.change-password.post');
    Route::post('/logout',         [AuthController::class, 'logout'])->name('logout');
});

// ============================
// AREA TERPROTEKSI (login + sudah ganti password)
// ============================
Route::middleware(['auth', 'must.change.password'])->group(function () {

    // Dashboard — semua role bisa, isinya beda
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ---- KARYAWAN & ATASAN ----
    Route::middleware('role:karyawan,atasan')->group(function () {
        Route::get('/cuti/ajukan',   [LeaveRequestController::class, 'create'])->name('leave.create');
        Route::post('/cuti/ajukan',  [LeaveRequestController::class, 'store'])->name('leave.store');
        Route::get('/cuti/riwayat',  [LeaveRequestController::class, 'myRequests'])->name('leave.my-requests');
        Route::get('/cuti/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave.show');
        Route::post('/cuti/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave.cancel');
        Route::delete('/cuti/{leaveRequest}',      [LeaveRequestController::class, 'destroy'])->name('leave.destroy');
    });

    // ---- ATASAN ----
    Route::middleware('role:atasan')->group(function () {
        Route::get('/approval',                   [LeaveRequestController::class, 'approvalList'])->name('leave.approval-list');
        Route::get('/approval/{leaveRequest}',    [LeaveRequestController::class, 'approvalShow'])->name('leave.approval-show');
        Route::post('/approval/{leaveRequest}',   [LeaveRequestController::class, 'approve'])->name('leave.approve');
    });

    // ---- HRD ----
    Route::middleware('role:hrd')->prefix('hrd')->name('hrd.')->group(function () {
        Route::get('/karyawan',                       [HrdController::class, 'userIndex'])->name('users.index');
        Route::get('/karyawan/tambah',                [HrdController::class, 'userCreate'])->name('users.create');
        Route::post('/karyawan',                      [HrdController::class, 'userStore'])->name('users.store');
        Route::get('/karyawan/{user}/edit',           [HrdController::class, 'userEdit'])->name('users.edit');
        Route::put('/karyawan/{user}',                [HrdController::class, 'userUpdate'])->name('users.update');
        Route::delete('/karyawan/{user}',             [HrdController::class, 'userDestroy'])->name('users.destroy');
        Route::post('/karyawan/{user}/reset-password',[HrdController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/karyawan/{user}/toggle-active', [HrdController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('/laporan/export-excel',           [HrdController::class, 'exportExcel'])->name('leave-report.excel');
        Route::get('/laporan/export-pdf',             [HrdController::class, 'exportPdf'])->name('leave-report.pdf');
        Route::get('/laporan',                        [HrdController::class, 'leaveReport'])->name('leave-report');
        Route::get('/laporan/{leaveRequest}',             [HrdController::class, 'showLeaveRequest'])->name('leave-request-detail');
        Route::get('/persetujuan-hr',                  [HrdController::class, 'approvalList'])->name('leave-approval');
        Route::post('/persetujuan-hr/{leaveRequest}',   [HrdController::class, 'approveRequest'])->name('leave-approval.process');
        Route::get('/laporan/cuti-tahunan',           [HrdController::class, 'annualLeaveQuotaReport'])->name('annual-leave-quota');
        Route::get('/laporan/cuti-tahunan/export-excel', [HrdController::class, 'exportAnnualLeaveQuotaExcel'])->name('annual-leave-quota.excel');
    });

});