<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_id'    => ['nullable', 'integer'],
            'date'           => ['nullable', 'date'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type'  => ['nullable', 'string', 'in:flat,percent'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_percent'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount_paid'    => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:CASH,CARD,UPI,BANK_TRANSFER,CREDIT'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
