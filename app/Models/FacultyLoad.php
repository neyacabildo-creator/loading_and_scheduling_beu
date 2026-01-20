<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyLoad extends Model
{
    protected $fillable = [
        'faculty_id',
        'department',
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
