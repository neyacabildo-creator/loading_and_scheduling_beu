<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UseSchoolConnection;

class Room extends Model
{
    use UseSchoolConnection;
    protected $fillable = [
        'room_number',
        'building',
        'capacity',
        'features',
        'status',
        'school_level',
    ];

    protected $casts = [
    ];
}
