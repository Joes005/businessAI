<?php

namespace App\Services\AI;

class AIToolRegistry
{
    /**
     * Get definitions of all registered tools for the AI Engine.
     */
    public function getToolDefinitions(): array
    {
        return [
            // READ TOOLS
            [
                'name'        => 'get_today_sales',
                'description' => 'Get total sales revenue and bill count for today.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name'        => 'get_sales_summary',
                'description' => 'Get sales revenue summary for a period (today, this_month, last_month).',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['today', 'this_month', 'last_month']],
                    ],
                ],
            ],
            [
                'name'        => 'get_low_stock_products',
                'description' => 'Get list of products that are low or out of stock.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name'        => 'get_pending_payments',
                'description' => 'Get list of customers with outstanding uncollected balance due.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name'        => 'get_top_products',
                'description' => 'Get top best-selling products ranked by total quantity sold.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'default' => 5],
                    ],
                ],
            ],

            // ANALYSIS TOOLS
            [
                'name'        => 'calculate_profit',
                'description' => 'Calculate verified net profit, cost of goods (COGS), and profit margin % server-side.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['today', 'this_month', 'last_month']],
                    ],
                ],
            ],
            [
                'name'        => 'calculate_sales_growth',
                'description' => 'Calculate period-over-period sales growth percentage.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name'        => 'calculate_inventory_risk',
                'description' => 'Calculate stockout risk and estimated days of inventory remaining based on sales velocity.',
                'risk_level'  => 'SAFE_READ',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],

            // ACTION TOOLS (Low / High Risk)
            [
                'name'        => 'update_stock',
                'description' => 'Update or adjust product inventory stock quantity.',
                'risk_level'  => 'HIGH_RISK_WRITE',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'quantity'   => ['type' => 'number'],
                        'type'       => ['type' => 'string', 'enum' => ['IN', 'OUT']],
                        'notes'      => ['type' => 'string'],
                    ],
                    'required' => ['product_id', 'quantity', 'type'],
                ],
            ],
            [
                'name'        => 'create_customer',
                'description' => 'Create a new customer profile.',
                'risk_level'  => 'LOW_RISK_WRITE',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'name'  => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                    ],
                    'required' => ['name'],
                ],
            ],

            // COMMUNICATION TOOLS
            [
                'name'        => 'prepare_payment_reminder',
                'description' => 'Prepare payment reminder message for customers with overdue balance.',
                'risk_level'  => 'HIGH_RISK_WRITE',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_name' => ['type' => 'string'],
                        'customer_id'   => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }
}
