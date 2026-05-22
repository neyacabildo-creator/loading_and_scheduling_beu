<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\UseSchoolConnection;

class ClassSchedule extends Model
{
    use UseSchoolConnection;
    protected $fillable = [
        'faculty_id',
        'subject',
        'grade_level',
        'section_name',
        'room_id',
        'day_of_week',
        'schedule_date',
        'start_time',
        'end_time',
        'status',
        'admin_approved',
        'approved_at',
        'approved_by',
        'principal_approved',
        'principal_approved_at',
        'principal_approved_by',
        'version',
        'change_log',
        'last_modified_by_admin',
    ];

    protected $casts = [
        'admin_approved' => 'boolean',
        'schedule_date' => 'date',
        'approved_at' => 'datetime',
        'principal_approved' => 'boolean',
        'principal_approved_at' => 'datetime',
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
