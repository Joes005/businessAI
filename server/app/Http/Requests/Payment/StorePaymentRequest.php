<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'    => ['required', 'exists:customers,id'],
            'invoice_id'     => ['nullable', 'exists:invoices,id'],
            'amount'         => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'in:CASH,CARD,UPI,BANK_TRANSFER,OTHER'],
            'payment_date'   => ['nullable', 'date'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
