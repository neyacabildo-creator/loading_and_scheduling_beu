<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\UseSchoolConnection;

class DSSRecommendation extends Model
{
    use UseSchoolConnection;
    protected $fillable = [
        'type',
        'priority',
        'issue',
        'solution',
        'status',
        'related_faculty_id',
    ];

    public function relatedFaculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_faculty_id');
    }
}
