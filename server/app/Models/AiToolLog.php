<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiToolLog extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'tool_name',
        'arguments',
        'execution_time_ms',
        'success',
        'error_message',
    ];

    protected $casts = [
        'arguments' => 'array',
        'success'   => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
