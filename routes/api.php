<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CashAppOAuthController;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\GroupInvitationController;
use App\Http\Controllers\API\PaymentRequestController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\CashAppWebhookController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\SmartPayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints (no auth required)
Route::get('/health', [HealthCheckController::class, 'check'])->name('health.check');
Route::get('/health/alive', [HealthCheckController::class, 'alive'])->name('health.alive');
Route::get('/health/ready', [HealthCheckController::class, 'ready'])->name('health.ready');

// Webhooks (no auth required)
Route::post('/webhooks/cashapp', [CashAppWebhookController::class, 'handle'])->name('webhooks.cashapp');

// Public routes
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Authentication routes with stricter rate limiting
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    // Device registration (allows both authenticated and guest users)
    Route::post('/devices/register', [DeviceController::class, 'register']);

    // Public invitation endpoints (no auth required to view invitation)
    Route::get('/invitations/{token}', [GroupInvitationController::class, 'show']);
    Route::post('/invitations/{token}/accept-magic-link', [GroupInvitationController::class, 'acceptMagicLink']);

    // Public payment request lookup by code (for mobile deep links)
    Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
    Route::get('/requests/by-code/{prCode}/square-invoice', [PaymentRequestController::class, 'squareInvoiceByCode']);

    // Member marks payment as sent (public endpoint - auth by PR code)
    Route::post('/requests/by-code/{prCode}/mark', [PaymentRequestController::class, 'markByCode']);

    // Cash App OAuth callback (public - handles OAuth redirect)
    Route::get('/cashapp/oauth/callback', [CashAppOAuthController::class, 'callback'])->name('api.cashapp.oauth.callback');
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);
    Route::post('/auth/onboarding/complete', [AuthController::class, 'completeOnboarding']);

    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
    Route::put('/groups/{group}/members/{member}', [GroupController::class, 'updateMember']);
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember']);
    Route::post('/groups/{group}/members/{member}/payment-requests', [PaymentRequestController::class, 'storeManual']);

    // Group Invitations
    Route::post('/groups/{group}/invitations', [GroupInvitationController::class, 'store']);
    Route::post('/invitations/{token}/accept', [GroupInvitationController::class, 'accept']);
    Route::delete('/groups/{group}/invitations/{invitation}', [GroupInvitationController::class, 'destroy']);

    // Payment Requests (Existing endpoints)
    Route::get('/payment-requests', [PaymentRequestController::class, 'index']);
    Route::get('/payment-requests/stats', [PaymentRequestController::class, 'stats']);
    Route::get('/payment-requests/{paymentRequest}', [PaymentRequestController::class, 'show']);
    Route::post('/payment-requests/{paymentRequest}/mark-paid', [PaymentRequestController::class, 'markPaid']);
    Route::post('/payment-requests/{paymentRequest}/retry', [PaymentRequestController::class, 'retry']);
    Route::get('/cycles/{cycle}/payment-requests', [PaymentRequestController::class, 'byCycle']);

    // Reports & exports
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv']);
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf']);

    // Payment Requests V2 (PR Code based endpoints - authenticated only)
    Route::post('/requests/by-code/{prCode}/confirm', [PaymentRequestController::class, 'confirmPayment']);
    Route::post('/requests/by-code/{prCode}/nudge', [PaymentRequestController::class, 'nudge']);
    Route::post('/requests/by-code/{prCode}/cancel', [PaymentRequestController::class, 'cancel']);
    Route::post('/requests/by-code/{prCode}/report-issue', [PaymentRequestController::class, 'reportIssue']);
    Route::post('/requests/by-code/{prCode}/resolve-issue', [PaymentRequestController::class, 'resolveIssue']);
    Route::post('/requests/by-code/{prCode}/undo', [PaymentRequestController::class, 'undoConfirmation']);
    Route::post('/cycles/{cycleId}/bulk-confirm', [PaymentRequestController::class, 'bulkConfirm']);

    // Square OAuth for mobile clients
    Route::get('/square/oauth/url', [\App\Http\Controllers\API\SquareOAuthApiController::class, 'issueAuthorizationUrl']);

    // Cash App OAuth endpoints
    Route::prefix('cashapp')->group(function () {
        Route::get('/oauth/authorize', [CashAppOAuthController::class, 'initiateOAuth']);
        Route::post('/resolve-cashtag', [CashAppOAuthController::class, 'resolveCashtag']);
        Route::delete('/disconnect', [CashAppOAuthController::class, 'disconnect']);
        Route::get('/status', [CashAppOAuthController::class, 'status']);
    });
});

// Mark payment as paid (public endpoint with optional auth)
Route::post('/requests/{id}/mark-paid', [SmartPayController::class, 'markPaid'])->name('api.mark-paid');
