<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    protected $fillable = [
        'faculty_id',
        'subject',
        'grade_section',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'student_count',
        'status',
        'admin_approved',
        'approved_at',
        'approved_by',
        'version',
        'change_log',
        'last_modified_by_admin',
    ];

    protected $casts = [
        'admin_approved' => 'boolean',
        'approved_at' => 'datetime',
        'last_modified_by_admin' => 'datetime',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
