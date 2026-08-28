<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Sales Summary Report.
     */
    public function sales(Request $request, ReportService $reportService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $data = $reportService->getSalesReport($businessId, $startDate, $endDate);

        return $this->successResponse($data, 'Sales report loaded.');
    }

    /**
     * Profit & Loss Statement.
     */
    public function profitLoss(Request $request, ReportService $reportService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $data = $reportService->getProfitLossReport($businessId, $startDate, $endDate);

        return $this->successResponse($data, 'Profit & Loss report loaded.');
    }

    /**
     * Inventory Valuation Report.
     */
    public function inventory(Request $request, ReportService $reportService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $data = $reportService->getInventoryValuationReport($businessId);

        return $this->successResponse($data, 'Inventory valuation report loaded.');
    }

    /**
     * Customer Debtors Report.
     */
    public function debtors(Request $request, ReportService $reportService): JsonResponse
    {
        $businessId = $request->user()->current_business_id;
        $data = $reportService->getDebtorsReport($businessId);

        return $this->successResponse($data, 'Debtors report loaded.');
    }
}
