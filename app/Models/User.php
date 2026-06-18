<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted()
    {
        static::creating(function (User $user) {
            if (empty($user->nik) && $user->join_date) {
                $user->nik = static::generateNikFromJoinDate($user->join_date);
            }
        });
    }

    protected $fillable = [
        'nik', 'full_name', 'email', 'password',
        'gender', 'birth_date', 'phone', 'position',
        'department_id', 'role', 'supervisor_id',
        'is_active', 'status_aktif', 'must_change_password', 'join_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'password'             => 'hashed',
        'is_active'            => 'boolean',
        'must_change_password' => 'boolean',
        'birth_date'           => 'date',
        'join_date'            => 'date',
    ];

    // =============================================
    // RELASI (Hubungan antar tabel)
    // =============================================

    // Setiap user punya satu departemen
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Setiap karyawan punya satu atasan
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // Satu atasan bisa punya banyak bawahan
    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    // Satu user bisa punya banyak pengajuan cuti
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // Kuota cuti user ini
    public function leaveQuotas()
    {
        return $this->hasMany(LeaveQuota::class);
    }

    // Notifikasi untuk user ini
    public function userNotifications()
    {
        // Diganti nama agar tidak bentrok dengan trait Notifiable bawaan Laravel
        return $this->hasMany(Notification::class);
    }

    // =============================================
    // HELPER METHODS (fungsi bantu pengecekan role)
    // =============================================

    public function isHrd(): bool     { return $this->role === 'hrd'; }
    public function isAtasan(): bool  { return $this->role === 'atasan'; }
    public function isKaryawan(): bool { return $this->role === 'karyawan'; }
    public function isOnCuti(): bool  { return $this->status_aktif === 'cuti'; }

    // Ambil sisa kuota cuti berdasarkan jenis dan tahun
    public function getRemainingQuota($leaveTypeId, $year = null): int
    {
        $year  = $year ?? now()->year;
        $quota = $this->leaveQuotas()
                      ->where('leave_type_id', $leaveTypeId)
                      ->where('year', $year)
                      ->first();
        return $quota ? $quota->remaining_quota : 0;
    }

    // Hitung notifikasi yang belum dibaca
    public function unreadNotificationsCount(): int
    {
        return $this->userNotifications()->where('is_read', false)->count();
    }

    public static function generateNikFromJoinDate($joinDate): string
    {
        $joinDate = Carbon::parse($joinDate);
        $prefix   = $joinDate->format('ym');

        $lastNik = static::whereYear('join_date', $joinDate->year)
            ->whereMonth('join_date', $joinDate->month)
            ->where('nik', 'like', $prefix . '%')
            ->orderByDesc('nik')
            ->value('nik');

        $sequence = 1;
        if ($lastNik) {
            $sequence = (int) substr($lastNik, 4) + 1;
        }

        return $prefix . str_pad($sequence, 2, '0', STR_PAD_LEFT);
    }
}