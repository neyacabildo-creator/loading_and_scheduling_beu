<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a teacher who is designated as shared across school levels.
 * Stored in the school-level admin database (mysql_jh or mysql_gs).
 * The $connection must be set at runtime based on the admin's school level.
 */
class SharedTeacher extends Model
{
    // Connection is set dynamically per school level.
    // Default to JH; GS controllers should call setConnection('mysql_gs').
    protected $connection = 'mysql_jh';

    protected $table = 'shared_teachers';

    protected $fillable = [
        'faculty_id',
        'teacher_name',
        'email',
        'school_level',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
