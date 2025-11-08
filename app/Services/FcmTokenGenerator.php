<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates OAuth2 access tokens for Firebase Cloud Messaging API v1
 */
class FcmTokenGenerator
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private const SCOPES = 'https://www.googleapis.com/auth/firebase.messaging';

    private array $serviceAccount;

    public function __construct()
    {
        $serviceAccountPath = config('services.fcm.service_account_path');

        if (! $serviceAccountPath || ! file_exists($serviceAccountPath)) {
            throw new \RuntimeException('FCM service account file not found');
        }

        $this->serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (! $this->serviceAccount) {
            throw new \RuntimeException('Invalid FCM service account JSON');
        }
    }

    /**
     * Get a valid OAuth2 access token (cached)
     */
    public function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate a new OAuth2 access token
     */
    private function generateAccessToken(): string
    {
        $now = time();
        $jwt = $this->createJwt($now);

        try {
            $response = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::error('FCM OAuth2 token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Failed to obtain FCM access token');
            }

            $data = $response->json();

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('FCM OAuth2 token generation error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create JWT for OAuth2 token request
     */
    private function createJwt(int $now): string
    {
        $payload = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => self::SCOPES,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        return JWT::encode($payload, $this->serviceAccount['private_key'], 'RS256');
    }
}
