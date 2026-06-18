<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveQuota extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'total_quota',
        'used_quota',
        'remaining_quota',
        'is_automatic',
        'carried_over_from_year',
        'expired_days',
        'opening_balance',
    ];

    protected $casts = [
        'total_quota'          => 'float',
        'used_quota'           => 'float',
        'remaining_quota'      => 'float',
        'is_automatic'         => 'boolean',
        'expired_days'         => 'float',
        'carried_over_from_year' => 'integer',
        'opening_balance'      => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT - VALIDASI SEBELUM SIMPAN
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Cek jika ini record cuti tahunan (CT) yang akan disimpan dengan total_quota = 0
            $leaveType = $model->leaveType ?? LeaveType::find($model->leave_type_id);
            if ($leaveType && $leaveType->code === 'CT') {
                $user = $model->user ?? User::find($model->user_id);
                if ($user) {
                    // Cek apakah user sudah berhak mendapat cuti tahunan (sudah 12 bulan kerja)
                    $yearsOfService = $user->join_date ? $user->join_date->diffInMonths(now()) : 0;
                    $isEntitled = $yearsOfService >= 12;

                    // Jika user sudah berhak tapi total_quota = 0, log warning
                    if ($isEntitled && $model->total_quota == 0) {
                        \Log::warning("Guard: Attempting to save annual leave (CT) with zero total_quota", [
                            'user_id' => $user->id,
                            'user_name' => $user->full_name,
                            'join_date' => $user->join_date?->toDateString(),
                            'years_of_service' => $yearsOfService,
                            'year' => $model->year,
                            'total_quota' => $model->total_quota,
                            'remaining_quota' => $model->remaining_quota,
                            'trace' => collect(debug_backtrace())->map(fn($t) => "{$t['file']}:{$t['line']}")->take(5),
                        ]);
                    }
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER UNLIMITED
    |--------------------------------------------------------------------------
    */

    // Apakah quota unlimited?
    public function isUnlimited(): bool
    {
        return $this->leaveType?->is_unlimited ?? false;
    }

    // Label sisa quota untuk tampilan
    public function getRemainingLabel(): string
    {
        if ($this->isUnlimited()) {
            return '∞ Tidak Terbatas';
        }

        $unit = $this->leaveType?->unit ?? 'hari';

        return $this->remaining_quota . ' ' . $unit;
    }

    // Apakah kuota ini dari carry forward?
    public function isCarriedOver(): bool
    {
        return $this->carried_over_from_year !== null;
    }

    // Ambil info carry forward
    public function getCarryOverInfo(): string
    {
        if (!$this->isCarriedOver()) {
            return '';
        }

        return "Dibawa dari tahun {$this->carried_over_from_year}";
    }

    // Hitung persentase penggunaan
    public function getUsagePercentage(): int
    {
        if ($this->total_quota === 0 || $this->isUnlimited()) {
            return 0;
        }

        return round(($this->used_quota / $this->total_quota) * 100);
    }

    // Status kuota untuk tampilan
    public function getStatusLabel(): string
    {
        if ($this->isUnlimited()) {
            return 'Tidak Terbatas';
        }

        $percentage = $this->getUsagePercentage();

        if ($percentage >= 100) {
            return 'Habis';
        } elseif ($percentage >= 80) {
            return 'Hampir Habis';
        } elseif ($percentage > 0) {
            return 'Sedang Digunakan';
        }

        return 'Belum Digunakan';
    }
}
