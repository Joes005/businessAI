<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'trigger_type', // LOW_STOCK_ALERT, DEBT_REMINDER, DAILY_SUMMARY
        'recipient',
        'message',
        'status',
    ];
}
