<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    use ApiResponse;

    /**
     * Check API system health and database connectivity.
     */
    public function check(): JsonResponse
    {
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (Throwable $e) {
            $dbConnected = false;
        }

        return $this->successResponse([
            'status'          => 'operational',
            'app_name'        => config('app.name', 'AI Business Copilot'),
            'environment'     => config('app.env', 'local'),
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'db_connected'    => $dbConnected,
            'timestamp'       => now()->toDateTimeString(),
        ], 'AI Business Copilot Backend API is operational');
    }
}
