<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'business_id',
        'type', // INVOICE, DEBT_REMINDER, WELCOME, PAYMENT_RECEIPT
        'template_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
