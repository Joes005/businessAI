<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VoiceCommandService
{
    /**
     * Process spoken voice transcript in English, Tanglish, or Tamil,
     * determine action intent, and return spoken audio response.
     */
    public function processVoiceCommand(string $transcript, int $businessId, string $language = 'en'): array
    {
        $normalized = Str::lower($transcript);
        $currency = '₹';
        $isTamil = ($language === 'ta') || Str::contains($normalized, [
            'பில்', 'பில்லிங்', 'சரக்கு', 'பொருள்', 'கடன்', 'காசு', 'விற்பனை', 'லாபம்', 'முகப்பு',
            'திற', 'காட்டு', 'எவ்வளவு', 'யாரு', 'இன்னைக்கு', 'podu', 'kattu', 'thira', 'yaaru', 'kadan'
        ]);

        // 1. Navigation Commands: Billing POS
        if (Str::contains($normalized, [
            'open billing', 'billing counter', 'go to pos', 'start billing', 'new bill', 'billing', 'bill',
            'பில்லிங்', 'பில்', 'பில் போடு', 'கடை பில்', 'bill podu', 'billing open'
        ])) {
            return [
                'action_type'     => 'navigate',
                'target_route'    => '/billing',
                'spoken_response' => $isTamil ? 'பில்லிங் கவுண்டரை திறக்கிறேன்.' : 'Opening POS Billing counter now.',
                'data'            => null,
            ];
        }

        // Navigation Commands: Products & Inventory
        if (Str::contains($normalized, [
            'open products', 'show products', 'inventory page', 'product list', 'manage stock', 'stock', 'products',
            'சரக்கு', 'பொருள்', 'ஸ்டாக்', 'பொருட்கள்', 'stock kattu', 'products open'
        ])) {
            return [
                'action_type'     => 'navigate',
                'target_route'    => '/products',
                'spoken_response' => $isTamil ? 'சரக்கு மற்றும் பொருட்கள் பக்கத்தை திறக்கிறேன்.' : 'Opening Products and Inventory page.',
                'data'            => null,
            ];
        }

        // Navigation Commands: Customers & Debtors
        if (Str::contains($normalized, [
            'open customers', 'show customers', 'customer ledger', 'debtors list', 'customers',
            'வாடிக்கையாளர்', 'கடன்', 'காசு', 'வாடிக்கையாளர்கள்', 'kadan', 'kaasu'
        ])) {
            return [
                'action_type'     => 'navigate',
                'target_route'    => '/customers',
                'spoken_response' => $isTamil ? 'வாடிக்கையாளர் கணக்கு பக்கத்தை திறக்கிறேன்.' : 'Opening Customer Ledger page.',
                'data'            => null,
            ];
        }

        // Navigation Commands: Reports
        if (Str::contains($normalized, [
            'open reports', 'show reports', 'financial reports', 'sales report', 'pnl', 'reports',
            'அறிக்கை', 'விற்பனை அறிக்கை', 'reports open', 'report'
        ])) {
            return [
                'action_type'     => 'navigate',
                'target_route'    => '/reports',
                'spoken_response' => $isTamil ? 'விற்பனை மற்றும் நிதி அறிக்கைகள் பக்கத்தை திறக்கிறேன்.' : 'Opening Financial and Sales Reports page.',
                'data'            => null,
            ];
        }

        // Navigation Commands: Dashboard / Home
        if (Str::contains($normalized, [
            'open dashboard', 'go home', 'show dashboard', 'overview', 'dashboard', 'home',
            'முகப்பு', 'டாஷ்போர்டு', 'home po'
        ])) {
            return [
                'action_type'     => 'navigate',
                'target_route'    => '/',
                'spoken_response' => $isTamil ? 'டாஷ்போர்டு முகப்பு பக்கத்தை திறக்கிறேன்.' : 'Opening Dashboard Overview.',
                'data'            => null,
            ];
        }

        // 2. Query Commands: Sales Today
        if (Str::contains($normalized, [
            'today sales', 'sell today', 'today\'s sales', 'how much did i sell',
            'இன்னைக்கு சேல்ஸ்', 'விற்பனை எவ்வளோ', 'இன்று விற்பனை', 'sales evvalavu'
        ])) {
            $today = Carbon::today();
            $sales = (float) Invoice::where('business_id', $businessId)->whereDate('date', $today)->sum('grand_total');
            $count = Invoice::where('business_id', $businessId)->whereDate('date', $today)->count();

            $spoken = $isTamil
                ? "இன்று நீங்கள் {$currency}" . number_format($sales, 2) . " விற்பனை செய்துள்ளீர்கள்."
                : "Today you sold {$currency}" . number_format($sales, 2) . " across {$count} bills.";

            return [
                'action_type'     => 'query',
                'target_route'    => '/reports',
                'spoken_response' => $spoken,
                'data'            => ['sales' => $sales, 'count' => $count],
            ];
        }

        // Query Commands: Debtors / Who Owes Money
        if (Str::contains($normalized, [
            'who owes', 'money owed', 'debtors', 'pending payments', 'collect money',
            'யாரு காசு', 'கடன் யாரு', 'யாரு காசு தரணும்', 'yaaru kaasu', 'kadan yaaru'
        ])) {
            $debtorsCount = Customer::where('business_id', $businessId)
                                    ->whereHas('invoices', fn($q) => $q->where('balance_due', '>', 0))
                                    ->count();
            $totalOwed = (float) Invoice::where('business_id', $businessId)->sum('balance_due');

            $spoken = $isTamil
                ? "{$debtorsCount} வாடிக்கையாளர்கள் மொத்தம் {$currency}" . number_format($totalOwed, 2) . " கடன் நிலுவை வைத்துள்ளனர்."
                : "You have {$debtorsCount} customers owing a total of {$currency}" . number_format($totalOwed, 2) . ". Opening customer ledger now.";

            return [
                'action_type'     => 'navigate',
                'target_route'    => '/customers',
                'spoken_response' => $spoken,
                'data'            => ['total_owed' => $totalOwed, 'debtors_count' => $debtorsCount],
            ];
        }

        // Query Commands: Low Stock
        if (Str::contains($normalized, [
            'low stock', 'out of stock', 'items low', 'restock',
            'குறைந்த இருப்பு', 'ஸ்டாக் எவ்வளோ', 'stock evvalavu'
        ])) {
            $lowStockCount = Product::where('business_id', $businessId)
                                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                                    ->count();

            $spoken = $isTamil
                ? "{$lowStockCount} பொருட்கள் குறைந்த இருப்பில் உள்ளன. பொருட்கள் பக்கத்தை திறக்கிறேன்."
                : "There are {$lowStockCount} items running low on stock. Opening products page.";

            return [
                'action_type'     => 'navigate',
                'target_route'    => '/products',
                'spoken_response' => $spoken,
                'data'            => ['low_stock_count' => $lowStockCount],
            ];
        }

        // Fallback
        $fallback = $isTamil
            ? "நான் கேட்டது: '{$transcript}'. 'பில்லிங் திற', 'யாரு காசு தரணும்', அல்லது 'சேல்ஸ் எவ்வளோ' என்று கூறி பாருங்கள்."
            : "I heard: '{$transcript}'. Try saying 'Open billing', 'Who owes me money', or 'Today sales'.";

        return [
            'action_type'     => 'unknown',
            'target_route'    => null,
            'spoken_response' => $fallback,
            'data'            => null,
        ];
    }
}
