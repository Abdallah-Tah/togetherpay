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
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->user()?->id
            ?? $request->user('sanctum')?->id
            ?? auth('sanctum')->id();

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
