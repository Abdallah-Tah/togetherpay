<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddMemberRequest;
use App\Http\Requests\API\CreateGroupRequest;
use App\Http\Requests\API\UpdateGroupRequest;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of groups (owned by user only)
     * Members should NOT see groups they're part of - they only see their payment requests
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only return groups owned by user
        // Members can view their payment requests but not browse groups
        $ownedGroups = $user->ownedGroups()
            ->with(['members.user', 'cycles' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($group) {
                return $group->toArray();
            });

        return response()->json([
            'owned_groups' => $ownedGroups,
            // Intentionally empty - members don't see groups list
            'member_groups' => [],
        ]);
    }

    /**
     * Create a new group
     */
    public function store(CreateGroupRequest $request): JsonResponse
    {
        $group = Group::create([
            'owner_id' => $request->user()->id,
            'name' => $request->name,
            'timezone' => $request->timezone ?? 'America/New_York',
            'currency' => $request->currency ?? 'USD',
            'cadence' => $request->cadence ?? 'MONTHLY',
            'due_day' => $request->due_day ?? 1,
            'grace_days' => $request->grace_days ?? 7,
            'total_amount_cents' => $request->total_amount ? ($request->total_amount * 100) : null,
            'status' => 'active',
        ]);

        // Update owner role if not already
        if (! $request->user()->isOwner()) {
            $request->user()->update(['role' => 'owner']);
        }

        $group->load(['owner', 'activeMembers.user']);
        $groupArray = $group->toArray();
        $groupArray['members'] = $groupArray['active_members'] ?? [];
        unset($groupArray['active_members']);

        return response()->json([
            'group' => $groupArray,
        ], 201);
    }

    /**
     * Display the specified group
     */
    public function show(Request $request, Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $group->load([
            'owner',
            'members.user', // Load ALL members (including inactive) for proper filtering in the app
            'cycles' => function ($query) {
                $query->latest()->with('paymentRequests')->limit(5);
            },
        ]);

        $groupArray = $group->toArray();

        return response()->json([
            'group' => $groupArray,
        ]);
    }

    /**
     * Update the specified group
     */
    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        $group->update($request->validated());
        $group->load(['owner', 'activeMembers.user']);

        $groupArray = $group->toArray();
        $groupArray['members'] = $groupArray['active_members'] ?? [];
        unset($groupArray['active_members']);

        return response()->json([
            'group' => $groupArray,
        ]);
    }

    /**
     * Delete the specified group
     */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully',
        ]);
    }

    /**
     * Add member to group
     */
    public function addMember(AddMemberRequest $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        \Log::info('ADD MEMBER REQUEST', [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'email' => $request->email,
        ]);

        // Find user by email or phone (only check phone if provided)
        $user = User::where('email', $request->email)->first();

        if (! $user && $request->phone) {
            $user = User::where('phone', $request->phone)->first();
        }

        // If user doesn't exist, create a placeholder/invited user
        $isNewInvitation = false;
        if (! $user) {
            \Log::info('USER NOT FOUND - CREATING INVITATION', [
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            $requestedName = trim((string) $request->input('name'));
            $fallbackName = $request->email ? explode('@', $request->email)[0] : 'Invited User';

            $user = User::create([
                'name' => $requestedName !== '' ? $requestedName : $fallbackName,
                'email' => $request->email ?? $request->phone.'@placeholder.local',
                'phone' => $request->phone,
                'password' => \Hash::make(\Str::random(32)), // Random password until they register
                'role' => 'member',
                'email_verified_at' => null, // Not verified yet
                'is_placeholder' => true, // Mark as placeholder until they accept invitation
            ]);

            \Log::info('INVITATION USER CREATED', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $isNewInvitation = true;
        }

        if ($request->filled('name') && $user->is_placeholder) {
            $user->update(['name' => $request->name]);
        }

        \Log::info('FOUND USER', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Check if user already has a record in this group (including inactive/left members)
        $existingMember = $group->members()->where('user_id', $user->id)->first();

        if ($existingMember) {
            \Log::info('EXISTING MEMBER FOUND', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'current_status' => $existingMember->status,
            ]);

            // If already active, reject
            if ($existingMember->status === 'active') {
                \Log::warning('DUPLICATE ACTIVE MEMBER BLOCKED', [
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'message' => 'User is already a member of this group',
                ], 422);
            }

            // Reactivate if they left or are paused
            \Log::info('REACTIVATING MEMBER', [
                'group_id' => $group->id,
                'user_id' => $user->id,
                'previous_status' => $existingMember->status,
            ]);

            $existingMember->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'preferred_channel' => $request->preferred_channel ?? $existingMember->preferred_channel,
                'venmo_username' => $request->venmo_username ?? $existingMember->venmo_username,
                'cash_tag' => $request->cash_tag ?? $existingMember->cash_tag,
                'cash_phone' => $request->cash_phone ?? $existingMember->cash_phone,
                'cash_email' => $request->cash_email ?? $existingMember->cash_email,
                'share_type' => $request->share_type ?? $existingMember->share_type,
                'share_value' => $request->share_value ?? $existingMember->share_value,
                'status' => 'active',
            ]);

            $member = $existingMember;
        } else {
            // Create new member
            \Log::info('CREATING NEW MEMBER', [
                'group_id' => $group->id,
                'user_id' => $user->id,
            ]);

            $member = GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'preferred_channel' => $request->preferred_channel ?? 'VENMO',
                'venmo_username' => $request->venmo_username,
                'cash_tag' => $request->cash_tag,
                'cash_phone' => $request->cash_phone,
                'cash_email' => $request->cash_email,
                'share_type' => $request->share_type ?? 'AMOUNT',
                'share_value' => $request->share_value ?? 0,
                'status' => 'active',
            ]);
        }

        // Send invitation if this is a new user we just created
        if ($isNewInvitation && $request->email) {
            $invitation = GroupInvitation::create([
                'group_id' => $group->id,
                'invited_by_user_id' => $request->user()->id,
                'email' => $request->email,
                'phone' => $request->phone,
                'token' => GroupInvitation::generateToken(),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'member_data' => [
                    'name' => $request->name,
                    'preferred_channel' => $request->preferred_channel ?? 'CASH_APP',
                    'venmo_username' => $request->venmo_username,
                    'cash_tag' => $request->cash_tag,
                    'cash_phone' => $request->cash_phone,
                    'cash_email' => $request->cash_email,
                    'share_type' => $request->share_type ?? 'AMOUNT',
                    'share_value' => $request->share_value ?? 0,
                ],
            ]);

            // Send invitation notification
            try {
                $user->notify(new GroupInvitationNotification($invitation));
                \Log::info('INVITATION EMAIL SENT', [
                    'invitation_id' => $invitation->id,
                    'email' => $request->email,
                ]);
            } catch (\Exception $e) {
                \Log::error('FAILED TO SEND INVITATION EMAIL', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = $existingMember
            ? 'Member reactivated successfully'
            : ($isNewInvitation ? 'Invitation sent successfully' : 'Member added successfully');

        return response()->json([
            'member' => $member->load('user'),
            'message' => $message,
            'invitation_sent' => $isNewInvitation,
        ], 201);
    }

    /**
     * Update member settings
     */
    public function updateMember(Request $request, Group $group, GroupMember $member): JsonResponse
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'preferred_channel' => ['sometimes', 'in:VENMO,CASH_APP,CARD'],
            'venmo_username' => ['nullable', 'string'],
            'cash_tag' => ['nullable', 'string'],
            'cash_phone' => ['nullable', 'string'],
            'cash_email' => ['nullable', 'email'],
            'share_type' => ['sometimes', 'in:AMOUNT,PERCENT'],
            'share_value' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,paused,left'],
        ]);

        $member->update($validated);

        return response()->json([
            'member' => $member->load('user'),
        ]);
    }

    /**
     * Remove member from group
     */
    public function removeMember(Request $request, Group $group, GroupMember $member): JsonResponse
    {
        $this->authorize('update', $group);

        $member->update(['status' => 'left']);

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
