<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    use ApiResponse;

    /**
     * Generate 1-click WhatsApp deep link for Invoice.
     */
    public function invoiceLink(Invoice $invoice, WhatsAppService $whatsAppService): JsonResponse
    {
        $linkData = $whatsAppService->generateInvoiceLink($invoice);

        return $this->successResponse($linkData, 'WhatsApp invoice link generated.');
    }

    /**
     * Generate 1-click WhatsApp deep link for Debt Reminder.
     */
    public function reminderLink(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'amount'      => ['nullable', 'numeric'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $amount = isset($data['amount']) ? (float) $data['amount'] : null;

        $linkData = $whatsAppService->generateReminderLink($customer, $amount);

        return $this->successResponse($linkData, 'WhatsApp debt reminder link generated.');
    }

    /**
     * List WhatsApp templates for active business.
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $templates = WhatsAppTemplate::where('business_id', $businessId)->get();

        return $this->successResponse([
            'templates' => $templates,
        ], 'WhatsApp templates loaded.');
    }

    /**
     * Create or update a WhatsApp template.
     */
    public function updateTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'          => ['required', 'string', 'in:INVOICE,DEBT_REMINDER,WELCOME,PAYMENT_RECEIPT'],
            'template_text' => ['required', 'string'],
        ]);

        $businessId = $request->user()->current_business_id;

        $template = WhatsAppTemplate::updateOrCreate(
            ['business_id' => $businessId, 'type' => $data['type']],
            ['template_text' => $data['template_text'], 'is_active' => true]
        );

        return $this->successResponse([
            'template' => $template,
        ], "WhatsApp template for '{$template->type}' saved successfully.");
    }
}
