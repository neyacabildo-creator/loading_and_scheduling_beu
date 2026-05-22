<?php

namespace App\Models;

use App\Models\Traits\UseSchoolConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    use UseSchoolConnection;

    protected $fillable = [
        'report_type',
        'format',
        'scope',
        'filename',
        'row_count',
        'file_size',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
