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
        $validator = Validator::make($request->all(), [
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

        // Update or create device
        $device = Device::updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            [
                'user_id' => auth()->id(), // null if not authenticated
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'last_active_at' => now(),
            ]
        );

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
