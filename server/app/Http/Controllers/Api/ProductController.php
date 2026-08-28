<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * List and search products for active business.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Search by name, SKU, or Barcode
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by Category
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter Low Stock items
        if ($request->query('low_stock') === 'true' || $request->query('low_stock') === '1') {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        $perPage = $request->query('per_page', 50);
        $products = $query->latest()->paginate($perPage);

        // Calculate summary statistics
        $totalProducts = Product::count();
        $totalStockValue = Product::selectRaw('SUM(stock_quantity * purchase_price) as total_val')->value('total_val') ?: 0;
        $lowStockCount = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        return $this->successResponse([
            'products'          => $products,
            'total_products'    => $totalProducts,
            'total_stock_value' => round($totalStockValue, 2),
            'low_stock_count'   => $lowStockCount,
        ], 'Products loaded.');
    }

    /**
     * Create product with initial stock movement transaction.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $product = Product::create($data);

            // Record initial stock movement if quantity > 0
            if ($product->stock_quantity > 0) {
                StockMovement::create([
                    'product_id'   => $product->id,
                    'user_id'      => auth()->id(),
                    'type'         => 'PURCHASE',
                    'quantity'     => $product->stock_quantity,
                    'unit_cost'    => $product->purchase_price,
                    'stock_before' => 0,
                    'stock_after'  => $product->stock_quantity,
                    'notes'        => 'Initial stock setup during product creation',
                ]);
            }

            return $product;
        });

        return $this->successResponse([
            'product' => $product->load('category'),
        ], "Product '{$product->name}' created successfully.", 201);
    }

    /**
     * Display a specific product.
     */
    public function show(Product $product): JsonResponse
    {
        return $this->successResponse([
            'product' => $product->load(['category', 'stockMovements.user']),
        ], 'Product details loaded.');
    }

    /**
     * Update product details.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return $this->successResponse([
            'product' => $product->fresh(['category']),
        ], 'Product updated successfully.');
    }

    /**
     * Soft delete product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $productName = $product->name;
        $product->delete();

        return $this->successResponse(null, "Product '{$productName}' deleted.");
    }
}
