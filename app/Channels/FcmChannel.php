<?php

namespace App\Channels;

use App\Services\FcmTokenGenerator;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Send the given notification via FCM v1 API.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get notification payload
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        // Get all active devices for this user with FCM tokens
        $devices = $notifiable->devices()->whereNotNull('fcm_token')->get();

        if ($devices->isEmpty()) {
            Log::info('No FCM tokens found for user', ['user_id' => $notifiable->id]);

            return;
        }

        // Get OAuth2 access token
        try {
            $tokenGenerator = new FcmTokenGenerator();
            $accessToken = $tokenGenerator->getAccessToken();
        } catch (\Exception $e) {
            Log::error('Failed to generate FCM access token', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        // Get project ID from config
        $projectId = config('services.fcm.project_id');

        if (! $projectId) {
            Log::warning('FCM project ID not configured');

            return;
        }

        // Send notification to each device
        foreach ($devices as $device) {
            try {
                $message = $this->buildMessage($device->fcm_token, $payload);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    Log::info('FCM notification sent successfully', [
                        'user_id' => $notifiable->id,
                        'device_id' => $device->id,
                    ]);
                } else {
                    $responseBody = $response->json();
                    Log::error('FCM notification failed', [
                        'user_id' => $notifiable->id,
                        'device_id' => $device->id,
                        'status' => $response->status(),
                        'body' => $responseBody,
                    ]);

                    // If token is invalid, remove it
                    $errorCode = $responseBody['error']['details'][0]['errorCode'] ?? null;
                    if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                        $device->update(['fcm_token' => null]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('FCM notification exception', [
                    'user_id' => $notifiable->id,
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build FCM v1 message structure from legacy payload
     */
    private function buildMessage(string $token, array $payload): array
    {
        $message = [
            'token' => $token,
        ];

        // Map legacy notification format to v1
        if (isset($payload['notification'])) {
            $message['notification'] = $payload['notification'];
        }

        // Map legacy data format to v1
        if (isset($payload['data'])) {
            $message['data'] = $payload['data'];
        }

        // Map legacy android settings to v1
        if (isset($payload['android'])) {
            $message['android'] = $payload['android'];
        }

        // Map legacy apns settings to v1
        if (isset($payload['apns'])) {
            $message['apns'] = $payload['apns'];
        }

        return $message;
    }
}
