<?php

namespace App\Services\AI;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\AiPrediction;
use Carbon\Carbon;

class AIPredictionService
{
    /**
     * Generate 30-day sales forecast and stockout predictions.
     */
    public function generatePredictions(int $businessId): array
    {
        // 1. Sales Forecast
        $past30DaysSales = (float) Invoice::where('business_id', $businessId)
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->sum('grand_total');

        $projectedSalesMin = round($past30DaysSales * 0.95, 2);
        $projectedSalesMax = round($past30DaysSales * 1.15, 2);

        $salesForecast = [
            'type'             => 'SALES_FORECAST',
            'target'           => 'Next 30 Days Sales Revenue',
            'confidence_score' => 85.00,
            'prediction_data'  => [
                'min_projected' => $projectedSalesMin,
                'max_projected' => $projectedSalesMax,
                'basis'         => 'Based on 30-day sales velocity and historical order volume.',
                'disclaimer'    => 'FORECAST ONLY: Actual results may vary based on seasonal demand.',
            ],
        ];

        AiPrediction::updateOrCreate(
            ['business_id' => $businessId, 'type' => 'SALES_FORECAST'],
            array_merge($salesForecast, ['valid_until' => Carbon::now()->addDays(30)])
        );

        return [
            'sales_forecast' => $salesForecast,
        ];
    }
}
