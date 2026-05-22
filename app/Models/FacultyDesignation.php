<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UseSchoolConnection;

/**
 * FacultyDesignation
 *
 * Per-teacher designation type with enforced class and load-hour limits.
 * Stored in the school-specific admin DB (mysql_jh or mysql_gs).
 */
class FacultyDesignation extends Model
{
    use UseSchoolConnection;

    protected $table = 'faculty_designations';

    protected $fillable = [
        'faculty_id',
        'school_year',
        'designation_type',
        'max_classes',
        'max_load_hours',
        'department',
        'notes',
        'assigned_by',
    ];

    protected $casts = [
        'max_classes'    => 'integer',
        'max_load_hours' => 'decimal:2',
    ];

    /**
     * Default max_classes per designation type.
     * Used when creating a designation without explicit override.
     */
    public static array $defaultMaxClasses = [
        'regular'     => 6,
        'coordinator' => 3,
        'dept_head'   => 4,
        'shared'      => 4,
        'part_time'   => 3,
    ];

    /**
     * Default max_load_hours per designation type (in weekly hours).
     */
    public static array $defaultMaxLoadHours = [
        'regular'     => 24.00,
        'coordinator' => 12.00,
        'dept_head'   => 16.00,
        'shared'      => 16.00,
        'part_time'   => 12.00,
    ];
}
