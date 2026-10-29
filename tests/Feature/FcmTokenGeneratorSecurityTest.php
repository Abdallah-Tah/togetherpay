<?php

use App\Services\FcmTokenGenerator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Cache::flush();
    Http::preventStrayRequests();
});

/**
 * @return string The public key for a synthetic service account.
 */
function configureSecurityTestFcmAccount(int $bits): string
{
    $key = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'private_key_bits' => $bits,
    ]);
    openssl_pkey_export($key, $privateKey);

    Storage::disk('local')->put('firebase-security-test.json', json_encode([
        'client_email' => 'fcm-security@example.invalid',
        'private_key' => $privateKey,
    ], JSON_THROW_ON_ERROR));

    config(['services.fcm.service_account_path' => Storage::disk('local')->path('firebase-security-test.json')]);

    return openssl_pkey_get_details($key)['key'];
}

test('FCM signs a valid assertion with a strong RSA key and caches the access token', function () {
    $publicKey = configureSecurityTestFcmAccount(2048);
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'synthetic-access-token'], 200),
    ]);

    $generator = app(FcmTokenGenerator::class);

    expect($generator->getAccessToken())->toBe('synthetic-access-token');
    expect($generator->getAccessToken())->toBe('synthetic-access-token');

    Http::assertSent(function (Request $request) use ($publicKey) {
        $claims = JWT::decode($request['assertion'], new Key($publicKey, 'RS256'));

        return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && $claims->iss === 'fcm-security@example.invalid'
            && $claims->aud === 'https://oauth2.googleapis.com/token'
            && $claims->scope === 'https://www.googleapis.com/auth/firebase.messaging'
            && is_int($claims->iat)
            && $claims->exp - $claims->iat === 3600;
    });
    Http::assertSentCount(1);
});

test('FCM refuses a weak RSA key before making an outbound token request', function () {
    configureSecurityTestFcmAccount(1024);

    expect(fn () => app(FcmTokenGenerator::class)->getAccessToken())->toThrow(DomainException::class);

    Http::assertNothingSent();
});
