<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CustomerReminderController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CopilotController;
use App\Http\Controllers\Api\VoiceController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\AutomationController;

/*
|--------------------------------------------------------------------------
| API Routes - AI Business Copilot (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // System Health Check
    Route::get('/health', [HealthController::class, 'check']);

    // Public Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Authenticated Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Management
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Business Management & Onboarding
        Route::prefix('businesses')->group(function () {
            Route::get('/', [BusinessController::class, 'index']);
            Route::post('/', [BusinessController::class, 'store']);
            Route::post('/{business}/switch', [BusinessController::class, 'switchBusiness']);
        });

        // Dashboard Analytics API
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // AI Copilot Assistant API
        Route::prefix('copilot')->group(function () {
            Route::post('/chat', [CopilotController::class, 'chat']);
            Route::get('/insights', [CopilotController::class, 'insights']);
        });

        // Voice Commands API
        Route::post('/voice/command', [VoiceController::class, 'process']);

        // WhatsApp Integration API
        Route::prefix('whatsapp')->group(function () {
            Route::post('/invoice-link/{invoice}', [WhatsAppController::class, 'invoiceLink']);
            Route::post('/reminder-link', [WhatsAppController::class, 'reminderLink']);
            Route::get('/templates', [WhatsAppController::class, 'getTemplates']);
            Route::put('/templates', [WhatsAppController::class, 'updateTemplate']);
        });

        // Automation API
        Route::prefix('automation')->group(function () {
            Route::get('/logs', [AutomationController::class, 'logs']);
            Route::post('/run', [AutomationController::class, 'run']);
        });

        // Categories API
        Route::apiResource('categories', CategoryController::class)->only(['index', 'store']);

        // Products API
        Route::apiResource('products', ProductController::class);

        // Stock Movements & Adjustments API
        Route::prefix('stock')->group(function () {
            Route::post('/adjust', [StockMovementController::class, 'adjust']);
            Route::get('/movements/{product}', [StockMovementController::class, 'movements']);
        });

        // Billing & Invoices API
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'store', 'show']);

        // Customers & Ledger API
        Route::apiResource('customers', CustomerController::class);
        Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger']);

        // Payments API
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store']);

        // Follow-up Reminders API
        Route::prefix('reminders')->group(function () {
            Route::post('/', [CustomerReminderController::class, 'store']);
            Route::patch('/{reminder}/status', [CustomerReminderController::class, 'updateStatus']);
        });

        // Reports API
        Route::prefix('reports')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales']);
            Route::get('/profit-loss', [ReportController::class, 'profitLoss']);
            Route::get('/inventory', [ReportController::class, 'inventory']);
            Route::get('/debtors', [ReportController::class, 'debtors']);
        });
    });
});
