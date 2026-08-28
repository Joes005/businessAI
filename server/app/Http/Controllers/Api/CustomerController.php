<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * List and filter customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search by Name, Phone, or Email
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter Debtors Only ("Who owes money?")
        if ($request->query('debtors_only') === 'true' || $request->query('debtors_only') === '1') {
            $query->whereHas('invoices', function ($q) {
                $q->where('balance_due', '>', 0);
            });
        }

        $perPage = $request->query('per_page', 50);
        $customers = $query->latest('id')->paginate($perPage);

        // Aggregated Metrics
        $totalCustomers = Customer::count();
        $totalOutstanding = Invoice::sum('balance_due');
        $debtorsCount = Customer::whereHas('invoices', function ($q) {
            $q->where('balance_due', '>', 0);
        })->count();

        return $this->successResponse([
            'customers'         => $customers,
            'total_customers'   => $totalCustomers,
            'total_outstanding' => round($totalOutstanding, 2),
            'debtors_count'     => $debtorsCount,
        ], 'Customers loaded.');
    }

    /**
     * Create a new customer record.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return $this->successResponse([
            'customer' => $customer,
        ], "Customer '{$customer->name}' added successfully.", 201);
    }

    /**
     * Show customer details.
     */
    public function show(Customer $customer): JsonResponse
    {
        return $this->successResponse([
            'customer' => $customer->load(['invoices.items', 'payments', 'reminders']),
        ], 'Customer profile loaded.');
    }

    /**
     * Update customer profile.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return $this->successResponse([
            'customer' => $customer,
        ], 'Customer details updated.');
    }

    /**
     * Delete customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $name = $customer->name;
        $customer->delete();

        return $this->successResponse(null, "Customer '{$name}' deleted.");
    }

    /**
     * Retrieve full statement / ledger for a customer.
     */
    public function ledger(Customer $customer): JsonResponse
    {
        $invoices = $customer->invoices()->with('items')->get();
        $payments = $customer->payments()->get();
        $reminders = $customer->reminders()->get();

        return $this->successResponse([
            'customer'           => $customer,
            'invoices'           => $invoices,
            'payments'           => $payments,
            'reminders'          => $reminders,
            'total_purchased'    => $customer->total_purchased,
            'total_paid'         => $customer->total_paid,
            'outstanding_amount' => $customer->outstanding_amount,
        ], "Ledger statement loaded for {$customer->name}.");
    }
}
