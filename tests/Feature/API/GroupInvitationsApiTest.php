<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('increments resend tracking when resending an invitation', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $invitation = GroupInvitation::create([
        'group_id' => $group->id,
        'invited_by_user_id' => $owner->id,
        'email' => 'invitee@example.com',
        'phone' => null,
        'token' => GroupInvitation::generateToken(),
        'status' => 'pending',
        'resend_count' => 0,
        'expires_at' => now()->addDays(7),
        'member_data' => [
            'name' => 'Invitee One',
            'share_count' => 1,
        ],
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/v1/groups/{$group->id}/invitations/{$invitation->id}/resend");

    $response
        ->assertOk()
        ->assertJsonPath('invitation.resend_count', 1)
        ->assertJsonPath('invitation.status', 'pending');

    $invitation->refresh();

    expect($invitation->resend_count)->toBe(1)
        ->and($invitation->last_resent_at)->not->toBeNull();

    Notification::assertSentOnDemand(GroupInvitationNotification::class);
});

it('resends an existing pending invitation from store and tracks resend metadata', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'owner']);
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $existingInvitation = GroupInvitation::create([
        'group_id' => $group->id,
        'invited_by_user_id' => $owner->id,
        'email' => 'invitee@example.com',
        'phone' => null,
        'token' => GroupInvitation::generateToken(),
        'status' => 'pending',
        'resend_count' => 0,
        'expires_at' => now()->addDays(3),
        'member_data' => [
            'name' => 'Invitee One',
            'share_count' => 1,
        ],
    ]);

    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/v1/groups/{$group->id}/invitations", [
        'email' => 'invitee@example.com',
        'name' => 'Invitee One',
        'share_count' => 1,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('invitation.id', $existingInvitation->id)
        ->assertJsonPath('invitation.resend_count', 1);

    $existingInvitation->refresh();

    expect($existingInvitation->resend_count)->toBe(1)
        ->and($existingInvitation->last_resent_at)->not->toBeNull();

    Notification::assertSentOnDemand(GroupInvitationNotification::class);
});

it('returns accepted invitations in group details payload', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $acceptedUser = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $acceptedInvitation = GroupInvitation::create([
        'group_id' => $group->id,
        'invited_by_user_id' => $owner->id,
        'email' => $acceptedUser->email,
        'phone' => null,
        'token' => GroupInvitation::generateToken(),
        'status' => 'accepted',
        'resend_count' => 2,
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subDay(),
        'accepted_by_user_id' => $acceptedUser->id,
        'last_resent_at' => now()->subDays(2),
        'member_data' => [
            'name' => 'Accepted User',
            'share_count' => 1,
        ],
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson("/api/v1/groups/{$group->id}");

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $acceptedInvitation->id,
            'status' => 'accepted',
            'resend_count' => 2,
        ]);
});
