<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GroupMember;
use App\Services\CashAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashAppOAuthController extends Controller
{
    public function __construct(
        protected CashAppService $cashAppService
    ) {}

    /**
     * Initiate Cash App OAuth flow for a group member
     *
     * GET /api/v1/cashapp/oauth/authorize
     */
    public function initiateOAuth(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:group_members,id',
        ]);

        $memberId = $request->input('member_id');
        $member = GroupMember::findOrFail($memberId);

        // Verify the authenticated user is the member's user
        if ($request->user()->id !== $member->user_id) {
            return response()->json([
                'message' => 'Unauthorized to connect Cash App for this member',
            ], 403);
        }

        // Generate state token for CSRF protection
        $state = Str::random(40);

        // Store state in session or cache with member_id
        cache()->put("cashapp_oauth_state_{$state}", [
            'member_id' => $memberId,
            'user_id' => $request->user()->id,
        ], now()->addMinutes(10));

        // Generate redirect URI
        $redirectUri = route('api.cashapp.oauth.callback');

        // Get authorization URL
        $authUrl = $this->cashAppService->getAuthorizationUrl($redirectUri, $state);

        return response()->json([
            'authorization_url' => $authUrl,
            'state' => $state,
        ]);
    }

    /**
     * Handle Cash App OAuth callback
     *
     * GET /api/v1/cashapp/oauth/callback
     */
    public function callback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        $code = $request->input('code');
        $state = $request->input('state');

        // Verify state token
        $stateData = cache()->get("cashapp_oauth_state_{$state}");
        if (! $stateData) {
            return response()->json([
                'message' => 'Invalid or expired state token',
            ], 400);
        }

        // Delete state token to prevent reuse
        cache()->forget("cashapp_oauth_state_{$state}");

        $memberId = $stateData['member_id'];
        $userId = $stateData['user_id'];

        // Exchange code for tokens
        $redirectUri = route('api.cashapp.oauth.callback');
        $tokens = $this->cashAppService->exchangeCodeForTokens($code, $redirectUri);

        if (! $tokens || ! isset($tokens['customer_id'])) {
            Log::error('Cash App OAuth failed - no customer ID', [
                'member_id' => $memberId,
            ]);

            return response()->json([
                'message' => 'Failed to connect Cash App account',
            ], 500);
        }

        // Update member with Cash App customer ID
        $member = GroupMember::findOrFail($memberId);
        $member->update([
            'cash_app_customer_id' => $tokens['customer_id'],
            'payment_provider_metadata' => array_merge(
                $member->payment_provider_metadata ?? [],
                [
                    'cashapp_connected_at' => now()->toIso8601String(),
                    'cashapp_access_token' => $tokens['access_token'],
                    'cashapp_refresh_token' => $tokens['refresh_token'] ?? null,
                    'cashapp_expires_in' => $tokens['expires_in'] ?? null,
                ]
            ),
        ]);

        Log::info('Cash App account connected successfully', [
            'member_id' => $memberId,
            'customer_id' => $tokens['customer_id'],
        ]);

        // Redirect to success page or return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cash App account connected successfully',
                'customer_id' => $tokens['customer_id'],
            ]);
        }

        // Redirect to mobile deep link or web success page
        $deepLink = "togetherpay://cashapp/success?member_id={$memberId}";

        return redirect($deepLink);
    }

    /**
     * Resolve $cashtag to customer ID (alternative method)
     *
     * POST /api/v1/cashapp/resolve-cashtag
     */
    public function resolveCashtag(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:group_members,id',
            'cashtag' => 'required|string|regex:/^\$?[a-zA-Z0-9_]{1,20}$/',
        ]);

        $memberId = $request->input('member_id');
        $cashtag = $request->input('cashtag');

        $member = GroupMember::findOrFail($memberId);

        // Verify the authenticated user is the member's user
        if ($request->user()->id !== $member->user_id) {
            return response()->json([
                'message' => 'Unauthorized to update this member',
            ], 403);
        }

        // Resolve cashtag to customer ID
        $customerId = $this->cashAppService->resolveCustomerId($cashtag);

        if (! $customerId) {
            return response()->json([
                'message' => 'Could not resolve Cash App customer ID from cashtag',
                'cashtag' => $cashtag,
            ], 404);
        }

        // Update member with customer ID
        $member->update([
            'cash_app_customer_id' => $customerId,
            'payment_provider_metadata' => array_merge(
                $member->payment_provider_metadata ?? [],
                [
                    'cashapp_cashtag' => $cashtag,
                    'cashapp_resolved_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        Log::info('Cash App customer ID resolved and saved', [
            'member_id' => $memberId,
            'cashtag' => $cashtag,
            'customer_id' => $customerId,
        ]);

        return response()->json([
            'message' => 'Cash App account connected successfully',
            'customer_id' => $customerId,
            'cashtag' => $cashtag,
        ]);
    }

    /**
     * Disconnect Cash App account
     *
     * DELETE /api/v1/cashapp/disconnect
     */
    public function disconnect(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:group_members,id',
        ]);

        $memberId = $request->input('member_id');
        $member = GroupMember::findOrFail($memberId);

        // Verify the authenticated user is the member's user
        if ($request->user()->id !== $member->user_id) {
            return response()->json([
                'message' => 'Unauthorized to disconnect this member',
            ], 403);
        }

        // Remove Cash App customer ID
        $member->update([
            'cash_app_customer_id' => null,
            'payment_provider_metadata' => array_merge(
                $member->payment_provider_metadata ?? [],
                [
                    'cashapp_disconnected_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        Log::info('Cash App account disconnected', [
            'member_id' => $memberId,
        ]);

        return response()->json([
            'message' => 'Cash App account disconnected successfully',
        ]);
    }

    /**
     * Get Cash App connection status for a member
     *
     * GET /api/v1/cashapp/status
     */
    public function status(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:group_members,id',
        ]);

        $memberId = $request->input('member_id');
        $member = GroupMember::findOrFail($memberId);

        // Verify the authenticated user is the member's user
        if ($request->user()->id !== $member->user_id) {
            return response()->json([
                'message' => 'Unauthorized to view this member',
            ], 403);
        }

        $isConnected = ! empty($member->cash_app_customer_id);
        $metadata = $member->payment_provider_metadata ?? [];

        return response()->json([
            'connected' => $isConnected,
            'customer_id' => $member->cash_app_customer_id,
            'cashtag' => $metadata['cashapp_cashtag'] ?? null,
            'connected_at' => $metadata['cashapp_connected_at'] ?? $metadata['cashapp_resolved_at'] ?? null,
        ]);
    }
}
