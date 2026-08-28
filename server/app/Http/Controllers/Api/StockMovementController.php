<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\AdjustStockRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    use ApiResponse;

    /**
     * Perform traceable stock adjustment (Purchase, Restock, Return, Damage, Manual Adjustment).
     */
    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $stockBefore = $product->stock_quantity;
            $quantityChange = (float) $data['quantity'];
            $stockAfter = max(0, $stockBefore + $quantityChange);

            // Update Product Stock Quantity
            $product->update(['stock_quantity' => $stockAfter]);

            // Create Stock Movement Audit Record
            $movement = StockMovement::create([
                'product_id'   => $product->id,
                'user_id'      => auth()->id(),
                'type'         => $data['type'],
                'quantity'     => $quantityChange,
                'unit_cost'    => $data['unit_cost'] ?? $product->purchase_price,
                'stock_before' => $stockBefore,
                'stock_after'  => $stockAfter,
                'reference_no' => $data['reference_no'] ?? null,
                'notes'        => $data['notes'] ?? null,
            ]);

            return [
                'product'  => $product->fresh(['category']),
                'movement' => $movement,
            ];
        });

        return $this->successResponse($result, "Stock updated for {$result['product']->name}. New balance: {$result['product']->stock_quantity} {$result['product']->unit}");
    }

    /**
     * Get stock movement history for a product.
     */
    public function movements(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()->with('user')->get();

        return $this->successResponse([
            'product'   => $product,
            'movements' => $movements,
        ], 'Stock movements loaded.');
    }
}
