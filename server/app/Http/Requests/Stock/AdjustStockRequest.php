<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'exists:products,id'],
            'type'         => ['required', 'string', 'in:PURCHASE,SALE,ADJUSTMENT,RETURN,DAMAGE'],
            'quantity'     => ['required', 'numeric', 'not_in:0'], // Positive to add, negative to reduce
            'unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
