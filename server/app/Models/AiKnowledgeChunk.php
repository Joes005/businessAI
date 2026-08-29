<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeChunk extends Model
{
    protected $fillable = [
        'document_id',
        'business_id',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(AiKnowledgeDocument::class, 'document_id');
    }
}
