<?php

namespace App\Http\Controllers\Square;

use App\Http\Controllers\Controller;
use App\Services\Square\SquareWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SquareWebhookController extends Controller
{
    public function __construct(private SquareWebhookService $webhookService)
    {
    }

    /**
     * Handle incoming Square webhook
     */
    public function handle(Request $request)
    {
        try {
            // Get webhook data
            $payload = $request->json()->all();

            // Verify signature
            $signature = $request->header('X-Square-HmacSHA256-Signature');
            $timestamp = $request->header('X-Square-HmacSHA256-Timestamp');
            $body = $request->getContent();

            if (!$this->webhookService->verifySignature($body, $signature, $timestamp)) {
                Log::warning('Invalid Square webhook signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Process webhook
            $this->webhookService->handleWebhook($payload);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Square webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
