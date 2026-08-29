<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeDocument extends Model
{
    protected $fillable = [
        'business_id',
        'title',
        'category',
    ];

    public function chunks()
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }
}
