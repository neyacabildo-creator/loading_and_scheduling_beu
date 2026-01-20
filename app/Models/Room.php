<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'building',
        'capacity',
        'features',
        'has_laboratory',
        'has_projector',
        'has_ac',
        'status',
    ];

    protected $casts = [
        'has_laboratory' => 'boolean',
        'has_projector' => 'boolean',
        'has_ac' => 'boolean',
    ];
}
