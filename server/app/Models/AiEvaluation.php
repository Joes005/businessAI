<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEvaluation extends Model
{
    protected $fillable = [
        'category',
        'user_prompt',
        'expected_intent',
        'expected_tool',
        'expected_outcome_summary',
        'passed',
        'actual_response',
        'last_evaluated_at',
    ];

    protected $casts = [
        'passed'            => 'boolean',
        'last_evaluated_at' => 'datetime',
    ];
}
