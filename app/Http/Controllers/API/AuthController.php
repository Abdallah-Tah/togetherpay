<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'member',
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'onboarding_completed' => $user->hasCompletedOnboarding(),
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke old tokens if requested
        if ($request->revoke_old_tokens) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'onboarding_completed' => $user->hasCompletedOnboarding(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => array_merge($user->toArray(), [
                'onboarding_completed' => $user->hasCompletedOnboarding(),
            ]),
        ]);
    }

    /**
     * Complete user onboarding
     */
    public function completeOnboarding(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'billing_mode' => 'required|in:manual,auto_square',
        ]);

        if (! empty($validated['billing_mode'])) {
            $mode = $validated['billing_mode'];

            if ($mode === 'auto_square' && ! $user->hasSquareAccount()) {
                return response()->json([
                    'message' => 'Connect your Square account before enabling automatic Cash App billing.',
                ], 422);
            }

            $user->setPreferredBillingMode($mode);
        }

        // Complete onboarding
        $user->completeOnboarding();

        // Refresh user to get updated data
        $user = $user->fresh();

        // Log for debugging
        Log::info('Onboarding completed', [
            'user_id' => $user->id,
            'preferred_billing_mode' => $user->preferred_billing_mode,
            'onboarding_completed_at' => $user->onboarding_completed_at,
            'has_completed' => $user->hasCompletedOnboarding(),
        ]);

        return response()->json([
            'message' => 'Onboarding completed successfully',
            'user' => array_merge($user->toArray(), [
                'onboarding_completed' => $user->hasCompletedOnboarding(),
                'preferred_billing_mode' => $user->getPreferredBillingMode(),
            ]),
        ]);
    }

    /**
     * Update authenticated user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|nullable',
            'cash_tag' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^\$?[A-Za-z0-9_-]+$/'],
            'cash_phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cash_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'venmo_username' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^@?[A-Za-z0-9_-]+$/'],
            'preferred_payment_method' => ['sometimes', 'in:CASH_APP,VENMO,CARD'],
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.email' => 'sometimes|boolean',
            'notification_preferences.push' => 'sometimes|boolean',
            'notification_preferences.sms' => 'sometimes|boolean',
            'notification_preferences.payment_requests' => 'sometimes|boolean',
            'notification_preferences.payment_reminders' => 'sometimes|boolean',
            'notification_preferences.payment_confirmations' => 'sometimes|boolean',
            'notification_preferences.group_invitations' => 'sometimes|boolean',
            'notification_preferences.cycle_completions' => 'sometimes|boolean',
        ], [
            'cash_tag.regex' => 'Cash App tag may optionally start with $, e.g. $JohnDoe',
            'venmo_username.regex' => 'Venmo username may start with @ and can only include letters, numbers, dashes, or underscores',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update authenticated user's password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Please choose a different password.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}
