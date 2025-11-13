<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Square\SquareOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SquareOAuthApiController extends Controller
{
    public function __construct(private SquareOAuthService $oauthService)
    {
    }

    /**
     * Generate a Square authorization URL for the authenticated user.
     */
    public function issueAuthorizationUrl(Request $request): JsonResponse
    {
        $user = $request->user();

        $state = Str::random(40);

        Cache::put("square_oauth_state_{$state}", [
            'user_id' => $user->id,
            'is_mobile' => true,
        ], now()->addMinutes(15));

        $authUrl = $this->oauthService->getAuthorizationUrl($state);

        return response()->json([
            'authorization_url' => $authUrl,
            'state' => $state,
        ]);
    }
}
