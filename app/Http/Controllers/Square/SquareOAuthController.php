<?php

namespace App\Http\Controllers\Square;

use App\Http\Controllers\Controller;
use App\Services\Square\SquareOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SquareOAuthController extends Controller
{
    public function __construct(private SquareOAuthService $oauthService)
    {
    }

    /**
     * Redirect user to Square OAuth authorization
     */
    public function redirectToSquare(Request $request)
    {
        $user = Auth::user();
        $state = $request->query('state') ?? \Illuminate\Support\Str::random(40);

        Log::info('Square OAuth: Initiating authorization flow', [
            'user_id' => $user->id,
            'state' => substr($state, 0, 10) . '...', // Log first 10 chars for security
        ]);

        // Store state in session for verification
        session(['square_oauth_state' => $state]);

        $authUrl = $this->oauthService->getAuthorizationUrl($state);

        Log::info('Square OAuth: Redirecting to Square authorization', [
            'user_id' => $user->id,
            'auth_url_prefix' => substr($authUrl, 0, 50) . '...', // Log first 50 chars
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback from Square
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');
        $user = Auth::user();

        logger()->info('Square OAuth: Callback received', [
            'user_id' => $user->id,
            'has_code' => !empty($code),
            'has_state' => !empty($state),
            'has_error' => !empty($error),
            'error_message' => $error,
            'code_prefix' => $code ? substr($code, 0, 10) . '...' : null,
            'state_prefix' => $state ? substr($state, 0, 10) . '...' : null,
        ]);

        // Check for errors
        if ($error) {
            logger()->warning('Square OAuth: Authorization error returned', [
                'user_id' => $user->id,
                'error' => $error,
            ]);

            return redirect()
                ->route('square-account.edit')
                ->with('error', "Square connection failed: {$error}");
        }

        // Verify state matches
        $sessionState = session('square_oauth_state');
        if ($state !== $sessionState) {
            logger()->warning('Square OAuth: State mismatch detected', [
                'user_id' => $user->id,
                'received_state' => $state ? substr($state, 0, 10) . '...' : null,
                'session_state' => $sessionState ? substr($sessionState, 0, 10) . '...' : null,
            ]);

            return redirect()
                ->route('square-account.edit')
                ->with('error', 'Invalid state parameter');
        }

        try {
            logger()->info('Square OAuth: Proceeding with token exchange', [
                'user_id' => $user->id,
            ]);

            // Exchange code for token
            $tokenResponse = $this->oauthService->exchangeCodeForToken($code);
            $accessToken = $tokenResponse['access_token'];

            logger()->info('Square OAuth: Token obtained successfully', [
                'user_id' => $user->id,
                'merchant_id' => $tokenResponse['merchant_id'] ?? null,
            ]);

            // Get merchant info including location
            $merchantInfo = $this->oauthService->getMerchantInfo($accessToken);

            // Extract location_id (use first location if merchant has multiple)
            $locationId = null;
            if (isset($merchantInfo['merchant']) && isset($merchantInfo['merchant']['locations'])) {
                $locations = $merchantInfo['merchant']['locations'];
                if (!empty($locations)) {
                    $locationId = $locations[0]['id'] ?? null;
                    logger()->info('Square OAuth: Location selected', [
                        'user_id' => $user->id,
                        'location_id' => $locationId,
                        'location_name' => $locations[0]['name'] ?? null,
                        'total_locations' => count($locations),
                    ]);
                }
            }

            // Store encrypted token and merchant details
            // Use encrypt() for simple strings to avoid serialization issues
            $user->update([
                'square_access_token' => Crypt::encrypt($accessToken),
                'square_merchant_id' => $tokenResponse['merchant_id'] ?? null,
                'square_location_id' => $locationId,
                'square_connected_at' => now(),
            ]);

            logger()->info('Square OAuth: User credentials stored successfully', [
                'user_id' => $user->id,
                'merchant_id' => $tokenResponse['merchant_id'] ?? null,
                'location_id' => $locationId,
            ]);

            // Clear session state
            session()->forget('square_oauth_state');

            return redirect()
                ->route('square-account.edit')
                ->with('success', 'Square account connected successfully!');
        } catch (\Exception $e) {
            logger()->error('Square OAuth callback failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('square-account.edit')
                ->with('error', 'Failed to connect Square account. Please try again.');
        }
    }

    /**
     * Disconnect Square account
     */
    public function disconnect(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasSquareAccount()) {
            return redirect()
                ->route('square-account.edit')
                ->with('error', 'No Square account connected');
        }

        try {
            // Revoke token if possible
            if ($user->square_access_token) {
                $token = Crypt::decryptString($user->square_access_token);
                $this->oauthService->revokeAccessToken($token);
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to revoke Square token', [
                'error' => $e->getMessage(),
            ]);
        }

        // Clear Square fields
        $user->disconnectSquare();

        return redirect()
            ->route('square-account.edit')
            ->with('success', 'Square account disconnected');
    }
}
