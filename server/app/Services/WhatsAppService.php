<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Str;

class WhatsAppService
{
    /**
     * Clean phone number for wa.me link.
     */
    protected function formatPhone(?string $phone): string
    {
        if (empty($phone)) return '';
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        // Default to India country code 91 if 10 digits
        if (strlen($cleaned) === 10) {
            $cleaned = '91' . $cleaned;
        }
        return $cleaned;
    }

    /**
     * Generate 1-click WhatsApp deep link for Invoice.
     */
    public function generateInvoiceLink(Invoice $invoice): array
    {
        $business = $invoice->business;
        $businessName = $business ? $business->name : 'Our Store';
        $currency = ($business && $business->currency === 'USD') ? '$' : (($business && $business->currency === 'EUR') ? '€' : '₹');

        $template = WhatsAppTemplate::where('business_id', $invoice->business_id)
                                    ->where('type', 'INVOICE')
                                    ->first();

        $defaultMessage = "Hi {customer_name},\nThank you for shopping at *{business_name}*!\n\nHere is your invoice *#{invoice_number}*:\nGrand Total: *{currency}{grand_total}*\nPayment Method: {payment_method}\nStatus: {payment_status}\n\nHave a great day!";

        $text = $template ? $template->template_text : $defaultMessage;

        $replacements = [
            '{customer_name}'  => $invoice->customer_name,
            '{invoice_number}' => $invoice->invoice_number,
            '{grand_total}'    => number_format($invoice->grand_total, 2),
            '{currency}'       => $currency,
            '{payment_method}' => $invoice->payment_method,
            '{payment_status}' => $invoice->payment_status,
            '{business_name}'   => $businessName,
        ];

        $finalMessage = str_replace(array_keys($replacements), array_values($replacements), $text);
        $phone = $this->formatPhone($invoice->customer_phone);

        $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($finalMessage);

        return [
            'phone'        => $phone,
            'message'      => $finalMessage,
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    /**
     * Generate 1-click WhatsApp deep link for Debt Reminder.
     */
    public function generateReminderLink(Customer $customer, ?float $customAmount = null): array
    {
        $business = $customer->business;
        $businessName = $business ? $business->name : 'Our Store';
        $currency = ($business && $business->currency === 'USD') ? '$' : (($business && $business->currency === 'EUR') ? '€' : '₹');
        $amount = $customAmount ?? $customer->outstanding_amount;

        $template = WhatsAppTemplate::where('business_id', $customer->business_id)
                                    ->where('type', 'DEBT_REMINDER')
                                    ->first();

        $defaultMessage = "Hi {customer_name},\nFriendly reminder from *{business_name}*.\n\nYour outstanding balance of *{currency}{balance_due}* is pending. Kindly clear the balance via UPI, Cash, or Card at your earliest convenience.\n\nThank you!";

        $text = $template ? $template->template_text : $defaultMessage;

        $replacements = [
            '{customer_name}' => $customer->name,
            '{balance_due}'   => number_format($amount, 2),
            '{currency}'      => $currency,
            '{business_name}' => $businessName,
        ];

        $finalMessage = str_replace(array_keys($replacements), array_values($replacements), $text);
        $phone = $this->formatPhone($customer->phone);

        $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($finalMessage);

        return [
            'phone'        => $phone,
            'message'      => $finalMessage,
            'whatsapp_url' => $whatsappUrl,
        ];
    }
}
