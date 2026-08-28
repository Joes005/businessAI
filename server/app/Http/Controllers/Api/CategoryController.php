<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * List categories for active business.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->latest()->get();

        return $this->successResponse([
            'categories' => $categories,
        ], 'Categories retrieved.');
    }

    /**
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:20'],
        ]);

        $category = Category::create($data);

        return $this->successResponse([
            'category' => $category,
        ], 'Category created successfully.', 201);
    }
}
