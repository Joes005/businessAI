<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBusinessEvent extends Model
{
    protected $fillable = [
        'business_id',
        'event_type',
        'payload',
        'processed',
    ];

    protected $casts = [
        'payload'   => 'array',
        'processed' => 'boolean',
    ];
}
