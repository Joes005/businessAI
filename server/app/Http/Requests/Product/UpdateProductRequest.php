<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'category_id'         => ['nullable', 'exists:categories,id'],
            'sku'                 => ['nullable', 'string', 'max:100'],
            'barcode'             => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'unit'                => ['sometimes', 'required', 'string', 'max:20'],
            'purchase_price'      => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
