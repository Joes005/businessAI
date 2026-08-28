<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'invoice_id',
        'product_id',
        'product_name',
        'unit',
        'unit_price',
        'unit_cost',
        'quantity',
        'discount_amount',
        'total',
    ];

    protected $casts = [
        'unit_price'      => 'float',
        'unit_cost'       => 'float',
        'quantity'        => 'float',
        'discount_amount' => 'float',
        'total'           => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
