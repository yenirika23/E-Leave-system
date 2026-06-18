<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    protected $table = 'leave_requests';

    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'request_date',
        'start_date',
        'end_date',
        'total_days',

        // BARU
        'day_type',          // full / morning / afternoon
        'document_path',
        'document_name',

        // LAMA
        'reason',
        'notes',
        'status',
        'disetujui_oleh',
        'id_approver',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | CASTING
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'request_date' => 'date',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'approved_at'  => 'datetime',
        'total_days'   => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Relasi ke user / karyawan
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke jenis cuti
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Relasi ke approver / atasan
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Relasi notifikasi
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER STATUS
    |--------------------------------------------------------------------------
    */

    // Status menunggu
    public function isPending(): bool
    {
        return $this->status === 'menunggu';
    }

    // Status disetujui
    public function isApproved(): bool
    {
        return $this->status === 'disetujui';
    }

    // Status ditolak
    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }

    // Status dibatalkan
    public function isCancelled(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI AKSI
    |--------------------------------------------------------------------------
    */

    // Apakah pengajuan bisa dibatalkan?
    public function canBeCancelled(): bool
    {
    return $this->isPending(); // Hanya pending yang bisa dibatalkan
    }

    // Apakah pengajuan bisa dihapus?
    public function canBeDeleted(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER DAY TYPE
    |--------------------------------------------------------------------------
    */

    // Label tipe hari
    public function getDayTypeLabel(): string
    {
        return match ($this->day_type) {
            'morning'   => 'Pagi (08.00–12.00)',
            'afternoon' => 'Siang (13.00–17.00)',
            default     => 'Full Day',
        };
    }

    // Total hari dalam teks
    public function getTotalLabel(): string
    {
        if ($this->day_type !== 'full') {
            return '0.5 hari (' . $this->getDayTypeLabel() . ')';
        }

        return $this->total_days . ' hari';
    }

    protected static function booted()
    {
        // Jika status berubah dari disetujui ke status lain, kembalikan kuota otomatis
        static::updating(function (LeaveRequest $leaveRequest) {
            $originalStatus = $leaveRequest->getOriginal('status');
            $newStatus = $leaveRequest->status ?? $leaveRequest->getAttribute('status');

            if ($originalStatus === 'disetujui' && $newStatus !== 'disetujui') {
                try {
                    $service = app(LeaveQuotaService::class);

                    // Pastikan leaveType dan user ter-load
                    $user = $leaveRequest->user;
                    $leaveType = $leaveRequest->leaveType;

                    if ($user && $leaveType) {
                        $service->restoreQuotaOnRejection(
                            $user,
                            $leaveType,
                            $leaveRequest->day_type,
                            $leaveRequest->total_days,
                            $leaveRequest->start_date
                        );
                    }
                } catch (\Throwable $e) {
                    // Jangan hentikan proses update jika restore gagal; log agar bisa ditelusuri
                    logger()->error('Failed to restore quota on status change: ' . $e->getMessage());
                }
            }
        });
    }
}