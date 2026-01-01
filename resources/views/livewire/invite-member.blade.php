<?php

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Notifications\GroupInvitationNotification;
use function Livewire\Volt\{state, rules, mount};
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

state(['groupId', 'email', 'phone', 'name', 'shareCount' => 1]);

rules([
    'email' => 'required|email',
    'phone' => 'nullable|string',
    'name' => 'required|string|max:255',
    'shareCount' => 'required|integer|min:1',
]);

mount(fn($groupId) => $this->groupId = $groupId);

$invite = function () {
    $this->validate();

    $group = Group::findOrFail($this->groupId);

    // Check authorization
    if ($group->owner_id !== auth()->id()) {
        throw ValidationException::withMessages([
            'email' => ['You are not authorized to invite members to this group.'],
        ]);
    }

    // Check if user is already a member
    $existingMember = $group->members()
        ->whereHas('user', function ($query) {
            $query->where('email', $this->email);
        })
        ->first();

    if ($existingMember) {
        throw ValidationException::withMessages([
            'email' => ['This email is already a member of this group.'],
        ]);
    }

    // Check if there's already a pending invitation
    $existingInvitation = GroupInvitation::where('group_id', $group->id)
        ->where('email', $this->email)
        ->pending()
        ->first();

    if ($existingInvitation) {
        throw ValidationException::withMessages([
            'email' => ['An invitation has already been sent to this email.'],
        ]);
    }

    // Create invitation
    $invitation = GroupInvitation::create([
        'group_id' => $group->id,
        'invited_by_user_id' => auth()->id(),
        'email' => $this->email,
        'phone' => $this->phone,
        'token' => GroupInvitation::generateToken(),
        'expires_at' => now()->addDays(7),
        'member_data' => [
            'name' => $this->name,
            'share_count' => $this->shareCount,
        ],
    ]);

    // Send invitation email
    Notification::route('mail', $invitation->email)
        ->notify(new GroupInvitationNotification($invitation));

    // Reset form
    $this->reset(['email', 'phone', 'name', 'shareCount']);
    $this->shareCount = 1;

    session()->flash('success', 'Invitation sent successfully to ' . $invitation->email);

    $this->dispatch('invitation-sent');
};

?>

<div>
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Invite New Member</flux:heading>
            <flux:subheading>Send an invitation to join this group</flux:subheading>
        </div>

        @if (session('success'))
            <flux:banner variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:banner>
        @endif

        <form wire:submit="invite" class="space-y-6">
            <flux:input
                wire:model="name"
                label="Member Name"
                placeholder="John Doe"
                required
            />

            <flux:input
                wire:model="email"
                type="email"
                label="Email Address"
                placeholder="member@example.com"
                required
            />

            <flux:input
                wire:model="phone"
                type="tel"
                label="Phone Number (Optional)"
                placeholder="+1234567890"
            />

            <flux:input
                wire:model.number="shareCount"
                type="number"
                label="Share Count"
                min="1"
                required
            />

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    Send Invitation
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
