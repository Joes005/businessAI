<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTask extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'goal',
        'status',
        'plan',
        'current_step_index',
        'completed_at',
    ];

    protected $casts = [
        'plan'         => 'array',
        'completed_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(AiTaskStep::class, 'ai_task_id')->orderBy('step_number');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
