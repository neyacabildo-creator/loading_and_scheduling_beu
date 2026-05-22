<?php

namespace App\Models;

use App\Models\Traits\UseSchoolConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents one cell in a teacher's weekly "Master Loading Schedule" grid.
 *
 * Each record = one day × one time-slot for a specific teacher and school year.
 * The connection is resolved automatically by UseSchoolConnection (mysql_jh or mysql_gs)
 * based on the SetSchoolDatabase middleware.
 */
class MasterWeeklySchedule extends Model
{
    use UseSchoolConnection;

    protected $table = 'master_weekly_schedules';

    protected $fillable = [
        'faculty_id',
        'school_year',
        'subject_handled',
        'slot_order',
        'time_label',
        'time_start',
        'time_end',
        'day_of_week',
        'entry_type',
        'grade_section',
        'grade_level',
        'section_name',
        'substitute_teacher',
        'special_label',
        'created_by',
    ];

    protected $casts = [
        'slot_order' => 'integer',
        'faculty_id' => 'integer',
        'created_by' => 'integer',
    ];
}
