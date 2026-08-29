<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBusinessProfile extends Model
{
    protected $fillable = [
        'business_id',
        'profile_data',
    ];

    protected $casts = [
        'profile_data' => 'array',
    ];
}
