<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\InvitationOtpNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class GroupInvitationController extends Controller
{
    private const INVITE_OTP_EXPIRES_MINUTES = 10;
    private const INVITE_OTP_MAX_ATTEMPTS_PER_HOUR = 5;

    /**
     * Send invitation to join group
     */
    public function store(Request $request, Group $group)
    {
        // Only group owner can invite
        $this->authorize('update', $group);

        $validated = $request->validate([
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'name' => 'required|string|max:255',
            'share_count' => 'required|integer|min:1',
            'venmo_username' => 'nullable|string',
            'cashapp_cashtag' => 'nullable|string',
        ]);

        // Check if user is already a member
        $existingMember = $group->members()
            ->whereHas('user', function ($query) use ($validated) {
                $query->where('email', $validated['email']);
            })
            ->first();

        if ($existingMember) {
            throw ValidationException::withMessages([
                'email' => ['This email is already a member of this group.'],
            ]);
        }

        // Check if there's already a pending invitation
        $existingInvitation = GroupInvitation::where('group_id', $group->id)
            ->where('email', $validated['email'])
            ->pending()
            ->first();

        if ($existingInvitation) {
            Notification::route('mail', $existingInvitation->email)
                ->notify(new GroupInvitationNotification($existingInvitation));

            return response()->json([
                'message' => 'Invitation resent successfully.',
                'invitation' => [
                    'id' => $existingInvitation->id,
                    'email' => $existingInvitation->email,
                    'status' => $existingInvitation->status,
                    'expires_at' => $existingInvitation->expires_at,
                    'invitation_url' => url("/accept-invitation/{$existingInvitation->token}"),
                ],
            ], 200);
        }

        // Create invitation
        $invitation = GroupInvitation::create([
            'group_id' => $group->id,
            'invited_by_user_id' => auth()->id(),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'token' => GroupInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
            'member_data' => [
                'name' => $validated['name'],
                'share_count' => $validated['share_count'],
                'venmo_username' => $validated['venmo_username'] ?? null,
                'cashapp_cashtag' => $validated['cashapp_cashtag'] ?? null,
            ],
        ]);

        // Send invitation email
        Notification::route('mail', $invitation->email)
            ->notify(new GroupInvitationNotification($invitation));

        return response()->json([
            'message' => 'Invitation sent successfully',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at,
                'invitation_url' => url("/accept-invitation/{$invitation->token}"),
            ],
        ], 201);
    }

    /**
     * Get invitation details by token
     */
    public function show(string $token)
    {
        $invitation = GroupInvitation::where('token', $token)
            ->with(['group', 'invitedBy'])
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
        }

        return response()->json([
            'invitation' => [
                'id' => $invitation->id,
                'group' => [
                    'id' => $invitation->group->id,
                    'name' => $invitation->group->name,
                ],
                'invited_by' => [
                    'name' => $invitation->invitedBy->name,
                ],
                'email' => $invitation->email,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at,
                'is_valid' => $invitation->isValid(),
                'member_data' => $invitation->member_data,
            ],
        ]);
    }

    /**
     * Resend invitation email (optionally update email).
     */
    public function resend(Request $request, Group $group, GroupInvitation $invitation): JsonResponse
    {
        $this->authorize('update', $group);

        if ((int) $invitation->group_id !== (int) $group->id) {
            return response()->json([
                'message' => 'Invitation not found for this group.',
            ], 404);
        }

        if ($invitation->status === 'accepted') {
            return response()->json([
                'message' => 'Invitation has already been accepted.',
            ], 422);
        }

        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $newEmail = $validated['email'] ?? $invitation->email;
        if ($newEmail !== $invitation->email) {
            $existingMember = $group->members()
                ->whereHas('user', function ($query) use ($newEmail) {
                    $query->where('email', $newEmail);
                })
                ->first();

            if ($existingMember) {
                return response()->json([
                    'message' => 'This email is already a member of this group.',
                ], 422);
            }

            $invitation->email = $newEmail;
            $invitation->token = GroupInvitation::generateToken();
        }

        $invitation->status = 'pending';
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        Notification::route('mail', $invitation->email)
            ->notify(new GroupInvitationNotification($invitation));

        return response()->json([
            'message' => 'Invitation resent successfully.',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at,
                'invitation_url' => url("/accept-invitation/{$invitation->token}"),
            ],
        ]);
    }

    /**
     * Magic link: Accept invitation and setup account (for new users)
     * This allows users without accounts to accept invitations via magic link
     */
    public function acceptMagicLink(Request $request, string $token)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'cash_tag' => 'nullable|string',
            'venmo_username' => 'nullable|string',
        ]);

        $invitation = GroupInvitation::where('token', $token)
            ->with('group')
            ->firstOrFail();

        // Check if invitation is valid
        if (! $invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is no longer valid or has expired.',
            ], 422);
        }

        // Find the placeholder user by email
        $user = User::where('email', $invitation->email)
            ->where('is_placeholder', true)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'No pending invitation found for this email.',
            ], 404);
        }

        // Activate the user account with their chosen password
        DB::transaction(function () use ($user, $validated, $invitation) {
            $user->update([
                'name' => $validated['name'],
                'password' => \Hash::make($validated['password']),
                'cash_tag' => $validated['cash_tag'] ?? null,
                'venmo_username' => $validated['venmo_username'] ?? null,
                'is_placeholder' => false,
                'email_verified_at' => now(),
                'invitation_accepted_at' => now(),
                'invitation_token' => null,
            ]);

            // Mark invitation as accepted
            $invitation->markAsAccepted($user);
        });

        // Send welcome email for invitation signup
        $user->notify(new WelcomeNotification(isInvitation: true));

        // Create auth token for immediate login
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully! Welcome to TogetherPay.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'onboarding_completed' => $user->hasCompletedOnboarding(),
            ],
            'token' => $token,
            'group' => $invitation->group,
        ], 200);
    }

    /**
     * Request a one-time passcode for invitation onboarding.
     * The user opens the invite in the app, requests a code, then verifies + sets password.
     */
    public function requestOtp(Request $request, string $token)
    {
        $invitation = GroupInvitation::where('token', $token)
            ->with('group')
            ->firstOrFail();

        if (! $invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is no longer valid or has expired.',
            ], 422);
        }

        // Basic rate limit per invitation token (stored in cache).
        $rateKey = "invite:otp:rate:{$token}";
        $rate = Cache::get($rateKey, ['count' => 0, 'reset_at' => now()->addHour()->toIso8601String()]);
        $count = (int) ($rate['count'] ?? 0);
        $resetAt = $rate['reset_at'] ?? now()->addHour()->toIso8601String();
        $resetAtDt = \Carbon\Carbon::parse($resetAt);
        if ($resetAtDt->isPast()) {
            $count = 0;
            $resetAtDt = now()->addHour();
        }

        if ($count >= self::INVITE_OTP_MAX_ATTEMPTS_PER_HOUR) {
            return response()->json([
                'message' => 'Too many code requests. Please try again later.',
            ], 429);
        }

        $code = (string) random_int(100000, 999999);
        $otpKey = "invite:otp:{$token}";
        Cache::put($otpKey, Hash::make($code), now()->addMinutes(self::INVITE_OTP_EXPIRES_MINUTES));
        Cache::put($rateKey, [
            'count' => $count + 1,
            'reset_at' => $resetAtDt->toIso8601String(),
        ], $resetAtDt);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationOtpNotification($invitation, $code, self::INVITE_OTP_EXPIRES_MINUTES));

        return response()->json([
            'message' => 'Verification code sent',
            'expires_in_minutes' => self::INVITE_OTP_EXPIRES_MINUTES,
        ]);
    }

    /**
     * Verify one-time passcode and activate account + set password.
     */
    public function verifyOtp(Request $request, string $token)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cash_tag' => ['nullable', 'string'],
            'venmo_username' => ['nullable', 'string'],
        ]);

        $invitation = GroupInvitation::where('token', $token)
            ->with('group')
            ->firstOrFail();

        if (! $invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is no longer valid or has expired.',
            ], 422);
        }

        $otpKey = "invite:otp:{$token}";
        $hash = Cache::get($otpKey);
        if (! $hash || ! Hash::check((string) $validated['code'], $hash)) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        Cache::forget($otpKey);

        $user = User::where('email', $invitation->email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'No pending invitation found for this email.',
            ], 404);
        }

        DB::transaction(function () use ($user, $validated, $invitation) {
            $user->update([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'cash_tag' => $validated['cash_tag'] ?? null,
                'venmo_username' => $validated['venmo_username'] ?? null,
                'is_placeholder' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'invitation_accepted_at' => now(),
                'invitation_token' => null,
            ]);

            $invitation->markAsAccepted($user);
        });

        $user->notify(new WelcomeNotification(isInvitation: true));
        $authToken = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully! Welcome to TogetherPay.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'onboarding_completed' => $user->hasCompletedOnboarding(),
            ],
            'token' => $authToken,
            'group' => $invitation->group,
        ]);
    }

    /**
     * Accept invitation and join group (for authenticated users)
     */
    public function accept(Request $request, string $token)
    {
        $validated = $request->validate([
            'accept' => 'required|boolean',
        ]);

        $invitation = GroupInvitation::where('token', $token)
            ->with('group')
            ->firstOrFail();

        // Check if invitation is valid
        if (! $invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is no longer valid or has expired.',
            ], 422);
        }

        if (! $validated['accept']) {
            $invitation->update(['status' => 'cancelled']);

            return response()->json([
                'message' => 'Invitation declined',
            ]);
        }

        $user = auth()->user();

        // Check if user's email matches invitation email
        if ($user->email !== $invitation->email) {
            return response()->json([
                'message' => 'This invitation was sent to a different email address.',
            ], 403);
        }

        // Check if user is already a member
        $existingMember = $invitation->group->members()
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            $invitation->markAsAccepted($user);

            return response()->json([
                'message' => 'You are already a member of this group',
                'member' => $existingMember,
            ]);
        }

        // Add user to group
        DB::transaction(function () use ($invitation, $user) {
            // Create group member
            $member = GroupMember::create([
                'group_id' => $invitation->group_id,
                'user_id' => $user->id,
                'name' => $invitation->member_data['name'],
                'email' => $user->email,
                'phone' => $invitation->phone ?? $user->phone,
                'share_count' => $invitation->member_data['share_count'],
                'venmo_username' => $invitation->member_data['venmo_username'] ?? null,
                'cashapp_cashtag' => $invitation->member_data['cashapp_cashtag'] ?? null,
            ]);

            // Mark invitation as accepted
            $invitation->markAsAccepted($user);
        });

        $member = $invitation->group->members()->where('user_id', $user->id)->first();

        return response()->json([
            'message' => 'Successfully joined the group!',
            'member' => $member->load('user'),
            'group' => $invitation->group,
        ]);
    }

    /**
     * Cancel invitation (owner only)
     */
    public function destroy(Group $group, GroupInvitation $invitation)
    {
        $this->authorize('update', $group);

        if ($invitation->group_id !== $group->id) {
            abort(404);
        }

        $invitation->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Invitation cancelled successfully',
        ]);
    }
}
