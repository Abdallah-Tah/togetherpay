<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\PaymentRequestController;
use App\Http\Controllers\CashAppWebhookController;
use App\Http\Controllers\SmartPayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Webhooks (no auth required)
Route::post('/webhooks/cashapp', [CashAppWebhookController::class, 'handle'])->name('webhooks.cashapp');

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
    Route::put('/groups/{group}/members/{member}', [GroupController::class, 'updateMember']);
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember']);

    // Payment Requests (Existing endpoints)
    Route::get('/payment-requests', [PaymentRequestController::class, 'index']);
    Route::get('/payment-requests/stats', [PaymentRequestController::class, 'stats']);
    Route::get('/payment-requests/{paymentRequest}', [PaymentRequestController::class, 'show']);
    Route::post('/payment-requests/{paymentRequest}/mark-paid', [PaymentRequestController::class, 'markPaid']);
    Route::post('/payment-requests/{paymentRequest}/retry', [PaymentRequestController::class, 'retry']);
    Route::get('/cycles/{cycle}/payment-requests', [PaymentRequestController::class, 'byCycle']);

    // Payment Requests V2 (PR Code based endpoints)
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    Route::post('/requests/by-code/{prCode}/confirm', [PaymentRequestController::class, 'confirmPayment']);
    Route::post('/requests/by-code/{prCode}/nudge', [PaymentRequestController::class, 'nudge']);
    Route::post('/cycles/{cycleId}/bulk-confirm', [PaymentRequestController::class, 'bulkConfirm']);
});

// Mark payment as paid (public endpoint with optional auth)
Route::post('/requests/{id}/mark-paid', [SmartPayController::class, 'markPaid'])->name('api.mark-paid');
