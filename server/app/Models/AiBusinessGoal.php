<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBusinessGoal extends Model
{
    protected $fillable = [
        'business_id',
        'title',
        'metric_key',
        'baseline_value',
        'target_value',
        'current_value',
        'status',
    ];

    protected $casts = [
        'baseline_value' => 'float',
        'target_value'   => 'float',
        'current_value'  => 'float',
    ];
}
