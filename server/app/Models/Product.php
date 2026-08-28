<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'description',
        'unit',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'low_stock_threshold',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'purchase_price'      => 'float',
        'selling_price'       => 'float',
        'stock_quantity'      => 'float',
        'low_stock_threshold' => 'float',
        'is_active'           => 'boolean',
    ];

    protected $appends = ['is_low_stock'];

    /**
     * Compute if item has low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }
}
