<?php

namespace App\Http\Controllers;

use App\Services\SquareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SquareOAuthController extends Controller
{
    protected SquareService $squareService;

    public function __construct()
    {
        $this->squareService = new SquareService;
    }

    /**
     * Redirect user to Square OAuth authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Generate and store state for CSRF protection
        $state = Str::random(40);
        session(['square_oauth_state' => $state]);

        // Generate authorization URL
        $authUrl = $this->squareService->getAuthorizationUrl($state);

        Log::info('Redirecting user to Square OAuth', [
            'user_id' => $user->id,
            'state' => $state,
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle callback from Square OAuth.
     */
    public function callback(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Validate state to prevent CSRF
        $sessionState = session('square_oauth_state');
        $returnedState = $request->query('state');

        if (! $sessionState || $sessionState !== $returnedState) {
            Log::warning('Square OAuth state mismatch', [
                'user_id' => $user->id,
                'session_state' => $sessionState,
                'returned_state' => $returnedState,
            ]);

            return redirect()
                ->route('settings.payment')
                ->with('error', 'Square connection failed: Invalid state. Please try again.');
        }

        // Clear the state from session
        session()->forget('square_oauth_state');

        // Check for authorization code
        $code = $request->query('code');
        if (! $code) {
            Log::warning('Square OAuth callback missing code', [
                'user_id' => $user->id,
                'query' => $request->query(),
            ]);

            return redirect()
                ->route('settings.payment')
                ->with('error', 'Square connection failed: No authorization code received.');
        }

        try {
            // Exchange code for access token
            $tokenData = $this->squareService->obtainAccessToken($code);

            // Store Square connection
            $user->connectSquare(
                accessToken: $tokenData['access_token'],
                merchantId: $tokenData['merchant_id'],
                locationId: $tokenData['location_id'] ?? null
            );

            Log::info('Square OAuth successful', [
                'user_id' => $user->id,
                'merchant_id' => $tokenData['merchant_id'],
            ]);

            return redirect()
                ->route('settings.payment')
                ->with('success', 'Square account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Square OAuth token exchange failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('settings.payment')
                ->with('error', 'Square connection failed: '.$e->getMessage());
        }
    }

    /**
     * Disconnect user's Square account.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Check if user has any active auto-Square groups
        $activeAutoGroups = $user->ownedGroups()
            ->where('billing_mode', 'auto_square')
            ->where('status', '!=', 'CLOSED')
            ->count();

        if ($activeAutoGroups > 0) {
            return redirect()
                ->route('settings.payment')
                ->with('error', "Cannot disconnect Square while you have {$activeAutoGroups} active auto-Square groups. Please close or convert them first.");
        }

        // Disconnect Square
        $user->disconnectSquare();

        Log::info('Square account disconnected', [
            'user_id' => $user->id,
        ]);

        return redirect()
            ->route('settings.payment')
            ->with('success', 'Square account disconnected successfully.');
    }
}
