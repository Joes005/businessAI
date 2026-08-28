<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * List payment history log for active business.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with(['customer', 'invoice', 'user'])
                            ->latest('payment_date')
                            ->paginate(50);

        return $this->successResponse([
            'payments' => $payments,
        ], 'Payment log loaded.');
    }

    /**
     * Record payment towards customer debt or specific invoice.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $result = DB::transaction(function () use ($data, $user) {
            $paymentAmount = (float) $data['amount'];
            $customerId = $data['customer_id'];
            $invoiceId = $data['invoice_id'] ?? null;

            // 1. Create Payment Record
            $payment = Payment::create([
                'business_id'    => $user->current_business_id,
                'customer_id'    => $customerId,
                'invoice_id'     => $invoiceId,
                'user_id'        => $user->id,
                'payment_date'   => $data['payment_date'] ?? now()->toDateString(),
                'amount'         => $paymentAmount,
                'payment_method' => strtoupper($data['payment_method']),
                'reference_no'   => $data['reference_no'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);

            // 2. Allocate payment to Invoice(s)
            $remainingPayment = $paymentAmount;

            if ($invoiceId) {
                $invoice = Invoice::lockForUpdate()->find($invoiceId);
                if ($invoice && $invoice->balance_due > 0) {
                    $apply = min($remainingPayment, $invoice->balance_due);
                    $newPaid = $invoice->amount_paid + $apply;
                    $newBalance = max(0, $invoice->grand_total - $newPaid);
                    $status = $newBalance == 0 ? 'PAID' : 'PARTIAL';

                    $invoice->update([
                        'amount_paid'    => $newPaid,
                        'balance_due'    => $newBalance,
                        'payment_status' => $status,
                    ]);
                }
            } else {
                // Auto-allocate payment across customer's oldest pending invoices
                $pendingInvoices = Invoice::where('customer_id', $customerId)
                                          ->where('balance_due', '>', 0)
                                          ->orderBy('date', 'asc')
                                          ->lockForUpdate()
                                          ->get();

                foreach ($pendingInvoices as $inv) {
                    if ($remainingPayment <= 0) break;

                    $apply = min($remainingPayment, $inv->balance_due);
                    $newPaid = $inv->amount_paid + $apply;
                    $newBalance = max(0, $inv->grand_total - $newPaid);
                    $status = $newBalance == 0 ? 'PAID' : 'PARTIAL';

                    $inv->update([
                        'amount_paid'    => $newPaid,
                        'balance_due'    => $newBalance,
                        'payment_status' => $status,
                    ]);

                    $remainingPayment -= $apply;
                }
            }

            $customer = Customer::findOrFail($customerId);

            return [
                'payment'  => $payment,
                'customer' => $customer,
            ];
        });

        return $this->successResponse([
            'payment'  => $result['payment'],
            'customer' => $result['customer'],
        ], "Payment of {$result['payment']->amount} recorded for {$result['customer']->name}.", 201);
    }
}
