<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTaskStep extends Model
{
    protected $fillable = [
        'ai_task_id',
        'step_number',
        'tool_name',
        'arguments',
        'risk_level',
        'status',
        'result',
        'error',
        'verification_status',
    ];

    protected $casts = [
        'arguments' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(AiTask::class, 'ai_task_id');
    }
}
