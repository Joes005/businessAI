<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'sender',
        'text',
        'metrics',
        'data_type',
        'data',
        'suggested_actions',
        'tool_calls',
    ];

    protected $casts = [
        'metrics'           => 'array',
        'data'              => 'array',
        'suggested_actions' => 'array',
        'tool_calls'        => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
