<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * Register a device with FCM token
     */
    public function register(Request $request)
    {
        \Log::info('Device registration attempt', [
            'headers' => $request->headers->all(),
            'payload' => $request->except(['fcm_token']),
            'has_fcm_token' => $request->has('fcm_token'),
        ]);

        $payload = $request->all();

        if (isset($payload['platform'])) {
            $payload['platform'] = strtolower((string) $payload['platform']);
        }

        $validator = Validator::make($payload, [
            'fcm_token' => 'required|string',
            'platform' => 'required|in:ios,android',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Device registration validation failed', [
                'errors' => $validator->errors(),
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->user()?->id
            ?? $request->user('sanctum')?->id
            ?? auth('sanctum')->id();

        \Log::info('Device registration user context', [
            'user_id' => $userId,
            'has_token' => $request->bearerToken() !== null,
        ]);

        // Update or create device
        $device = Device::updateOrCreate(
            ['fcm_token' => $payload['fcm_token']],
            [
                'platform' => $payload['platform'],
                'device_name' => $payload['device_name'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
                'last_active_at' => now(),
            ]
        );

        if ($userId && $device->user_id !== $userId) {
            $device->forceFill(['user_id' => $userId])->save();
        }

        \Log::info('Device registered successfully', [
            'device_id' => $device->id,
            'user_id' => $device->user_id,
            'platform' => $device->platform,
        ]);

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'last_active_at' => $device->last_active_at,
            ],
        ], 200);
    }
}
