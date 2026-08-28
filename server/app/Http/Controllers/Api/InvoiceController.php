<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * List paginated invoices for active business.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['items', 'user']);

        // Search by Invoice Number or Customer Name/Phone
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by Payment Status (PAID, PARTIAL, UNPAID)
        if ($status = $request->query('payment_status')) {
            $query->where('payment_status', $status);
        }

        // Filter by Date
        if ($date = $request->query('date')) {
            $query->whereDate('date', $date);
        }

        $perPage = $request->query('per_page', 20);
        $invoices = $query->latest('id')->paginate($perPage);

        // Daily Summary Metrics
        $todaySalesTotal = Invoice::whereDate('date', now()->toDateString())->sum('grand_total');
        $todayInvoicesCount = Invoice::whereDate('date', now()->toDateString())->count();
        $totalBalanceDue = Invoice::sum('balance_due');

        return $this->successResponse([
            'invoices'             => $invoices,
            'today_sales_total'    => round($todaySalesTotal, 2),
            'today_invoices_count' => $todayInvoicesCount,
            'total_balance_due'    => round($totalBalanceDue, 2),
        ], 'Invoices loaded.');
    }

    /**
     * Store new invoice and deduct stock.
     */
    public function store(StoreInvoiceRequest $request, InvoiceService $invoiceService): JsonResponse
    {
        $user = $request->user();
        $invoice = $invoiceService->createInvoice(
            $request->validated(),
            $user->id,
            $user->current_business_id
        );

        return $this->successResponse([
            'invoice' => $invoice,
        ], "Invoice #{$invoice->invoice_number} created successfully.", 201);
    }

    /**
     * Display a specific invoice with items.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return $this->successResponse([
            'invoice' => $invoice->load(['items.product', 'user', 'business']),
        ], 'Invoice details loaded.');
    }
}
