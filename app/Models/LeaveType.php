<?php
// app/Models/LeaveType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name', 'code', 'default_quota', 'unit',
        'description', 'is_active', 'is_unlimited', 'allow_half_day',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_unlimited'   => 'boolean',
        'allow_half_day' => 'boolean',
    ];

    // Relasi
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
    public function leaveQuotas()   { return $this->hasMany(LeaveQuota::class); }

    // Scope: hanya yang aktif
    public function scopeActive($query) { return $query->where('is_active', true); }

    // Helper: apakah unlimited?
    public function isUnlimited(): bool { return $this->is_unlimited; }

    // Helper: label kuota untuk tampilan
    public function getQuotaLabel(): string
    {
        if ($this->is_unlimited) return 'Tidak Terbatas';
        if ($this->allow_half_day) return $this->default_quota . ' sesi';
        return $this->default_quota . ' hari';
    }
}