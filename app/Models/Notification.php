<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Menggunakan tabel notifications sesuai migration
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'is_read', 'leave_request_id',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function user()         { return $this->belongsTo(User::class); }
    public function leaveRequest() { return $this->belongsTo(LeaveRequest::class); }
}