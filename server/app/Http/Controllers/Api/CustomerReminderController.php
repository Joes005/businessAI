<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerReminder;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerReminderController extends Controller
{
    use ApiResponse;

    /**
     * Create follow-up payment reminder.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'   => ['required', 'exists:customers,id'],
            'amount'        => ['nullable', 'numeric', 'min:0'],
            'reminder_date' => ['required', 'date'],
            'notes'         => ['nullable', 'string'],
        ]);

        $reminder = CustomerReminder::create([
            'business_id'   => $request->user()->current_business_id,
            'customer_id'   => $data['customer_id'],
            'user_id'       => $request->user()->id,
            'amount'        => $data['amount'] ?? 0.00,
            'reminder_date' => $data['reminder_date'],
            'status'        => 'PENDING',
            'notes'         => $data['notes'] ?? null,
        ]);

        return $this->successResponse([
            'reminder' => $reminder->load('customer'),
        ], 'Payment follow-up reminder scheduled.', 201);
    }

    /**
     * Update reminder status (COMPLETED / CANCELLED).
     */
    public function updateStatus(Request $request, CustomerReminder $reminder): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:PENDING,COMPLETED,CANCELLED'],
        ]);

        $reminder->update(['status' => $data['status']]);

        return $this->successResponse([
            'reminder' => $reminder,
        ], "Reminder marked as {$data['status']}.");
    }
}
