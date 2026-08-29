<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'business_id',
        'type',
        'title',
        'severity',
        'problem',
        'impact',
        'recommendation',
        'supporting_data',
        'status',
    ];

    protected $casts = [
        'supporting_data' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
