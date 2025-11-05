<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Send the given notification via FCM.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get FCM server key from config
        $serverKey = config('services.fcm.server_key');

        if (! $serverKey) {
            Log::warning('FCM server key not configured');

            return;
        }

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

        // Send notification to each device
        foreach ($devices as $device) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', array_merge([
                    'to' => $device->fcm_token,
                    'priority' => 'high',
                ], $payload));

                if ($response->successful()) {
                    Log::info('FCM notification sent successfully', [
                        'user_id' => $notifiable->id,
                        'device_id' => $device->id,
                    ]);
                } else {
                    Log::error('FCM notification failed', [
                        'user_id' => $notifiable->id,
                        'device_id' => $device->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    // If token is invalid, remove it
                    if ($response->status() === 400 || $response->status() === 404) {
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
}
