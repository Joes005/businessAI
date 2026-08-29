<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Str;

class LocalRuleProvider implements AIProviderInterface
{
    public function generateResponse(array $messages, array $tools = [], array $options = []): array
    {
        $lastMessage = end($messages);
        $userPrompt = trim($lastMessage['content'] ?? '');
        $normalized = Str::lower($userPrompt);

        $toolCalls = [];
        $content = '';

        // 1. Greetings & Casual Conversational Starters
        if (in_array($normalized, ['hi', 'hii', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'vanakkam', 'hi boss', 'hello boss', 'vanakkam boss', 'hii good morning', 'hi good morning', 'good morning!'])) {
            $isTanglish = Str::contains($normalized, ['vanakkam', 'boss']) || (rand(0, 1) === 1);
            if (Str::contains($normalized, 'vanakkam') || Str::contains($normalized, 'boss')) {
                $content = "Vanakkam boss! ☀️ Good morning! Naan unga **AI Business Copilot**. Innaiku unga store sales, profit, low stock, illana customer kadan pathi enna venum nalum kekkalam. How can I help you today?";
            } else {
                $content = "Good morning! ☀️ Hello! I am your **AI Business Employee**.\n\nI am ready to help you track today's sales, analyze monthly profit, monitor low stock, or manage customer receivables. What would you like to check first?";
            }
            return [
                'content'    => $content,
                'tool_calls' => [],
                'usage'      => ['prompt_tokens' => 20, 'completion_tokens' => 35, 'total_tokens' => 55],
            ];
        }

        // 2. Appreciation & Friendly Closings
        if (Str::contains($normalized, ['thanks', 'thank you', 'nandri', 'romba nandri', 'super', 'great', 'awesome', 'nice', 'ok thanks', 'ok super', 'fine'])) {
            $content = "You're very welcome boss! 👍 Always here to help your business grow smoothly. Let me know whenever you need anything else!";
            return [
                'content'    => $content,
                'tool_calls' => [],
                'usage'      => ['prompt_tokens' => 15, 'completion_tokens' => 25, 'total_tokens' => 40],
            ];
        }

        // 3. Business Status / Epdi poguthu business?
        if (Str::contains($normalized, ['how is my business', 'how is business', 'epdi poguthu', 'business epdi', 'business status', 'how are things', 'business health'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_today_sales',
                'arguments' => [],
            ];
        }

        // Priority 1: Financial Profit & Margins
        elseif (Str::contains($normalized, ['profit', 'margin', 'pnl', 'p&l', 'earnings', 'labam', 'லாபம்'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'calculate_profit',
                'arguments' => ['period' => 'this_month'],
            ];
        }
        // Priority 2: Debtors & Collections
        elseif (Str::contains($normalized, ['owe', 'money', 'debt', 'debtors', 'who owes', 'collect', 'receivable', 'kadan', 'kaasu', 'taranum', 'கடன்', 'காசு'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_pending_payments',
                'arguments' => [],
            ];
        }
        // Priority 3: Low Stock & Restocking (Exclude stock market queries)
        elseif (Str::contains($normalized, ['low stock', 'out of stock', 'restock', 'items low', 'stock kammiya', 'irukka', 'சரக்கு', 'பொருள்']) || (Str::contains($normalized, 'stock') && !Str::contains($normalized, ['price', 'market', 'apple', 'share']))) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_low_stock_products',
                'arguments' => [],
            ];
        }
        // Priority 4: Today Sales & Tanglish
        elseif (Str::contains($normalized, ['today', 'daily sales', 'sales today', 'sell today', 'innaiku', 'இன்னைக்கு சேல்ஸ்', 'விற்பனை'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_today_sales',
                'arguments' => [],
            ];
        }
        // Priority 5: Month Sales Summary
        elseif (Str::contains($normalized, ['this month', 'month sales', 'monthly sales', 'revenue this month', 'indha month'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_sales_summary',
                'arguments' => ['period' => 'this_month'],
            ];
        }
        // Priority 6: Bestsellers
        elseif (Str::contains($normalized, ['bestseller', 'best selling', 'top product', 'top items', 'most sold'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'get_top_products',
                'arguments' => ['limit' => 5],
            ];
        }
        // Priority 7: Payment Reminders Action
        elseif (Str::contains($normalized, ['reminder', 'send payment reminder', 'notify overdue', 'remind ravi'])) {
            $toolCalls[] = [
                'id'        => 'call_' . Str::random(8),
                'name'      => 'prepare_payment_reminder',
                'arguments' => ['customer_name' => 'Overdue Customer'],
            ];
        }

        if (empty($toolCalls)) {
            $content = "I am your **AI Business Employee**. I can analyze sales, calculate profits, manage stock, identify pending debts, and create reminders. How can I assist your business today?";
        }

        return [
            'content'    => $content,
            'tool_calls' => $toolCalls,
            'usage'      => [
                'prompt_tokens'     => 120,
                'completion_tokens' => 45,
                'total_tokens'      => 165,
            ],
        ];
    }
}
