<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'user_id',
        'type', // PURCHASE, SALE, ADJUSTMENT, RETURN, DAMAGE
        'quantity',
        'unit_cost',
        'stock_before',
        'stock_after',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'quantity'     => 'float',
        'unit_cost'    => 'float',
        'stock_before' => 'float',
        'stock_after'  => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
