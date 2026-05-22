<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UseSchoolConnection;

class ScheduleApproval extends Model
{
    use UseSchoolConnection;

    protected $fillable = [
        'schedule_id',
        'submitted_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'submission_notes',
        'revision_count',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
}
