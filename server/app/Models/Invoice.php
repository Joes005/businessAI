<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'customer_id',
        'user_id',
        'invoice_number',
        'customer_name',
        'customer_phone',
        'date',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'grand_total',
        'amount_paid',
        'balance_due',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'date'            => 'date',
        'subtotal'        => 'float',
        'discount_value'  => 'float',
        'discount_amount' => 'float',
        'tax_percent'     => 'float',
        'tax_amount'      => 'float',
        'grand_total'     => 'float',
        'amount_paid'     => 'float',
        'balance_due'     => 'float',
    ];

    /**
     * Boot helper to generate sequential invoice number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number) && $invoice->business_id) {
                $lastId = static::withoutGlobalScopes()
                                ->where('business_id', $invoice->business_id)
                                ->max('id') ?: 0;
                $invoice->invoice_number = 'INV-' . str_pad($lastId + 10001, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
