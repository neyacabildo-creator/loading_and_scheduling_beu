<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\UseSchoolConnection;

class ExportLog extends Model
{
    use UseSchoolConnection;
    protected $fillable = [
        'format',
        'data_selected',
        'filename',
        'file_path',
        'file_size',
        'status',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
