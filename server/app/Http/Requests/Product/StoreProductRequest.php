<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'category_id'         => ['nullable', 'exists:categories,id'],
            'sku'                 => ['nullable', 'string', 'max:100'],
            'barcode'             => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'unit'                => ['required', 'string', 'max:20'],
            'purchase_price'      => ['required', 'numeric', 'min:0'],
            'selling_price'       => ['required', 'numeric', 'min:0'],
            'stock_quantity'      => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
        ];
    }
}
