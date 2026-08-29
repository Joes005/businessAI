<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAction extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'action_type',
        'description',
        'payload',
        'risk_level',
        'status',
        'execution_result',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
