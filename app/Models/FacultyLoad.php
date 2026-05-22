<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\UseSchoolConnection;

class FacultyLoad extends Model
{
    use UseSchoolConnection;
    protected $fillable = [
        'faculty_id',
        'teacher_name',
        'grade_level',
        'subject',
        'classes_assigned',
        'load_hours',
        'status',
        'notes',
    ];

    protected $casts = [
        'load_hours' => 'decimal:2',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
}
