<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrediction extends Model
{
    protected $fillable = [
        'business_id',
        'type',
        'target',
        'prediction_data',
        'confidence_score',
        'valid_until',
    ];

    protected $casts = [
        'prediction_data'  => 'array',
        'confidence_score' => 'float',
        'valid_until'      => 'datetime',
    ];
}
