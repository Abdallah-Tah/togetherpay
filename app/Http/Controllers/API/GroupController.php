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
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Display a listing of groups the user owns or belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $ownedGroups = $user->ownedGroups()
            ->with([
                'members.user',
                'cycles' => function ($query) {
                    $query->latest()->with('paymentRequests')->limit(1);
                },
            ])
            ->get()
            ->map(function ($group) {
                $groupArray = $group->toArray();
                $groupArray['is_owner'] = true;
                $groupArray['member_count'] = $group->members
                    ->filter(fn ($member) => ($member->status ?? 'active') === 'active')
                    ->filter(fn ($member) => $member->user_id !== $group->owner_id)
                    ->count();
                return $groupArray;
            });

        $memberGroups = $user->groupMemberships()
            ->where('status', 'active')
            ->whereHas('group', function ($query) use ($user) {
                $query->where('owner_id', '!=', $user->id);
            })
            ->with(['group' => function ($query) {
                $query->with([
                    'members.user',
                    'cycles' => function ($cycleQuery) {
                        $cycleQuery->latest()->with('paymentRequests')->limit(1);
                    },
                ]);
            }])
            ->get()
            ->map(function ($membership) {
                $group = $membership->group;
                if (! $group) {
                    return null;
                }
                $groupArray = $group->toArray();
                $groupArray['is_owner'] = false;
                $groupArray['member_id'] = $membership->id;
                $groupArray['member_count'] = $group->members
                    ->filter(fn ($member) => ($member->status ?? 'active') === 'active')
                    ->filter(fn ($member) => $member->user_id !== $group->owner_id)
                    ->count();
                return $groupArray;
            })
            ->filter()
            ->values();

        return response()->json([
            'owned_groups' => $ownedGroups,
            'member_groups' => $memberGroups,
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
            'invitations' => function ($query) {
                $query->where('status', 'pending')->latest();
            },
            'cycles' => function ($query) {
                $query->latest()->with('paymentRequests')->limit(5);
            },
        ]);

        $memberEmails = $group->members
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values();

        $pendingInvitations = $group->invitations
            ->filter(function ($invitation) use ($memberEmails) {
                $email = strtolower(trim((string) $invitation->email));
                return $email !== '' && ! $memberEmails->contains($email);
            })
            ->values();

        $groupArray = $group->toArray();
        $groupArray['invitations'] = $pendingInvitations;

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

        // Find user by email only to avoid mismatching by phone.
        $user = User::where('email', $request->email)->first();

        if ($user && ! $request->boolean('allow_existing')) {
            return response()->json([
                'message' => 'Email already exists. Use the existing member option.',
            ], 422);
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

        $resolvedName = $request->name ?? $user->name;
        if (! $resolvedName || trim($resolvedName) === '') {
            $resolvedName = $request->email ? explode('@', $request->email)[0] : 'Member';
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
                'name' => $resolvedName,
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
                'name' => $resolvedName,
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
                    'name' => $resolvedName,
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

        // Ensure the member belongs to this group
        if ((int) $member->group_id !== (int) $group->id) {
            return response()->json([
                'message' => 'Member not found in this group',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($member->user_id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('users', 'phone')->ignore($member->user_id),
            ],
            'preferred_channel' => ['sometimes', 'in:VENMO,CASH_APP,CARD'],
            'venmo_username' => ['nullable', 'string'],
            'cash_tag' => ['nullable', 'string'],
            'cash_phone' => ['nullable', 'string'],
            'cash_email' => ['nullable', 'email'],
            'share_type' => ['sometimes', 'in:AMOUNT,PERCENT'],
            'share_value' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,paused,left'],
        ]);

        // Normalize payment handles when channel changes to avoid stale data.
        $updateData = $validated;
        if (Arr::exists($validated, 'preferred_channel')) {
            $channel = strtoupper((string) $validated['preferred_channel']);
            if ($channel === 'VENMO') {
                $updateData['cash_tag'] = null;
                $updateData['cash_phone'] = null;
                $updateData['cash_email'] = null;
            } elseif ($channel === 'CASH_APP') {
                $updateData['venmo_username'] = null;
            } else { // CARD or other
                $updateData['venmo_username'] = null;
                $updateData['cash_tag'] = null;
                $updateData['cash_phone'] = null;
                $updateData['cash_email'] = null;
            }
        }

        $member->update($updateData);

        // Sync core contact fields to the related user as well, so UI reflects edits.
        $user = $member->user;
        if ($user) {
            $userUpdates = Arr::only($validated, ['name', 'email', 'phone']);
            if (! empty($userUpdates)) {
                try {
                    $user->update($userUpdates);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to sync placeholder user fields on member update', [
                        'user_id' => $user->id,
                        'member_id' => $member->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'message' => 'Failed to update member contact details',
                    ], 422);
                }
            }
        }

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

        $member->delete();

        return response()->json([
            'message' => 'Member removed successfully',
        ]);
    }
}
