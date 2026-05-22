<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UseSchoolConnection;

/**
 * LoadConflictLog
 *
 * Immediate conflict record written at the moment a faculty load or
 * schedule entry is created / edited. Admin can see all open conflicts
 * in one place and resolve them without manual board-checking.
 *
 * Stored in the school-specific admin DB (mysql_jh or mysql_gs).
 */
class LoadConflictLog extends Model
{
    use UseSchoolConnection;

    protected $table = 'load_conflict_log';

    protected $fillable = [
        'faculty_id',
        'conflict_type',
        'description',
        'severity',
        'related_schedule_id',
        'related_load_id',
        'detected_at',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
