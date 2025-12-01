<?php

use App\Models\GroupInvitation;
use App\Models\GroupMember;
use function Livewire\Volt\{state, mount, computed, layout};
use Illuminate\Support\Facades\DB;

layout('components.layouts.guest');

state(['token', 'invitation', 'error', 'success']);

mount(function ($token) {
    $this->token = $token;

    $invitation = GroupInvitation::where('token', $token)
        ->with(['group', 'invitedBy'])
        ->first();

    if (!$invitation) {
        $this->error = 'Invalid invitation link.';
        return;
    }

    if ($invitation->isExpired()) {
        $invitation->update(['status' => 'expired']);
        $this->error = 'This invitation has expired.';
        return;
    }

    if ($invitation->status !== 'pending') {
        $this->error = 'This invitation is no longer available.';
        return;
    }

    $this->invitation = $invitation;
});

$accept = function () {
    if (!auth()->check()) {
        return redirect()->route('login')->with('intended', url()->current());
    }

    $invitation = GroupInvitation::where('token', $this->token)->first();

    if (!$invitation || !$invitation->isValid()) {
        $this->error = 'This invitation is no longer valid.';
        return;
    }

    $user = auth()->user();

    // Check if user's email matches invitation email
    if ($user->email !== $invitation->email) {
        $this->error = 'This invitation was sent to ' . $invitation->email . '. Please log in with that email address.';
        return;
    }

    // Check if already a member
    $existingMember = $invitation->group->members()
        ->where('user_id', $user->id)
        ->first();

    if ($existingMember) {
        $invitation->markAsAccepted($user);
        return redirect()->route('groups.show', $invitation->group_id)
            ->with('success', 'You are already a member of this group.');
    }

    // Add user to group
    DB::transaction(function () use ($invitation, $user) {
        GroupMember::create([
            'group_id' => $invitation->group_id,
            'user_id' => $user->id,
            'name' => $invitation->member_data['name'] ?? $user->name,
            'email' => $user->email,
            'phone' => $invitation->phone ?? $user->phone,
            'preferred_channel' => $invitation->member_data['preferred_channel'] ?? 'CASH_APP',
            'venmo_username' => $invitation->member_data['venmo_username'] ?? null,
            'cash_tag' => $invitation->member_data['cash_tag'] ?? null,
            'cash_phone' => $invitation->member_data['cash_phone'] ?? null,
            'cash_email' => $invitation->member_data['cash_email'] ?? null,
            'share_type' => $invitation->member_data['share_type'] ?? 'AMOUNT',
            'share_value' => $invitation->member_data['share_value'] ?? 0,
            'status' => 'active',
        ]);

        $invitation->markAsAccepted($user);
    });

    return redirect()->route('dashboard')
        ->with('success', 'Successfully joined ' . $invitation->group->name . '! You will receive payment requests when they are created.');
};

$decline = function () {
    $invitation = GroupInvitation::where('token', $this->token)->first();

    if ($invitation) {
        $invitation->update(['status' => 'cancelled']);
    }

    $this->success = 'Invitation declined.';
};

?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        @if ($error)
            <flux:card class="text-center space-y-6">
                <div class="flex justify-center">
                    <flux:icon.x-circle class="w-16 h-16 text-red-500" />
                </div>
                <div>
                    <flux:heading size="lg">Invitation Error</flux:heading>
                    <flux:subheading class="mt-2">{{ $error }}</flux:subheading>
                </div>
                <flux:button href="/" variant="primary">
                    Go to Home
                </flux:button>
            </flux:card>
        @elseif ($success)
            <flux:card class="text-center space-y-6">
                <div class="flex justify-center">
                    <flux:icon.check-circle class="w-16 h-16 text-green-500" />
                </div>
                <div>
                    <flux:heading size="lg">{{ $success }}</flux:heading>
                </div>
                <flux:button href="/" variant="primary">
                    Go to Home
                </flux:button>
            </flux:card>
        @elseif ($invitation)
            <flux:card class="space-y-6">
                <div class="text-center">
                    <flux:heading size="lg">You're Invited!</flux:heading>
                    <flux:subheading class="mt-2">
                        {{ $invitation->invitedBy?->name ?? 'Someone' }} has invited you to join
                    </flux:subheading>
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 space-y-4">
                    <div class="text-center">
                        <h3 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                            {{ $invitation->group->name }}
                        </h3>
                    </div>

                    <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        @if(!empty($invitation->member_data['name']))
                        <div class="flex justify-between">
                            <span>Your name:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $invitation->member_data['name'] }}</span>
                        </div>
                        @endif
                        @if(!empty($invitation->member_data['share_type']) && !empty($invitation->member_data['share_value']))
                        <div class="flex justify-between">
                            <span>Your share:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                @if($invitation->member_data['share_type'] === 'AMOUNT')
                                    ${{ number_format($invitation->member_data['share_value'], 2) }}
                                @else
                                    {{ $invitation->member_data['share_value'] }}%
                                @endif
                            </span>
                        </div>
                        @endif
                        @if (!empty($invitation->member_data['venmo_username']))
                            <div class="flex justify-between">
                                <span>Venmo:</span>
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $invitation->member_data['venmo_username'] }}</span>
                            </div>
                        @endif
                        @if (!empty($invitation->member_data['cash_tag']))
                            <div class="flex justify-between">
                                <span>Cash App:</span>
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $invitation->member_data['cash_tag'] }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <span>Expires:</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $invitation->expires_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        By accepting this invitation, you'll be able to receive and pay payment requests from this group via Venmo or Cash App.
                    </p>
                </div>

                @auth
                    @if (auth()->user()->email === $invitation->email)
                        <div class="flex gap-3">
                            <flux:button wire:click="accept" variant="primary" class="flex-1">
                                Accept Invitation
                            </flux:button>
                            <flux:button wire:click="decline" variant="ghost" class="flex-1">
                                Decline
                            </flux:button>
                        </div>
                    @else
                        <div class="text-center space-y-4">
                            <flux:banner variant="warning">
                                This invitation was sent to {{ $invitation->email }}.
                                Please log out and log in with that email address to accept.
                            </flux:banner>
                            <flux:button href="{{ route('logout') }}" variant="primary">
                                Log Out
                            </flux:button>
                        </div>
                    @endif
                @else
                    <div class="space-y-3">
                        <flux:banner variant="info">
                            Please log in or create an account to accept this invitation.
                        </flux:banner>
                        <div class="flex gap-3">
                            <flux:button href="{{ route('login') }}" variant="primary" class="flex-1">
                                Log In
                            </flux:button>
                            <flux:button href="{{ route('register', ['invitation' => $invitation->token, 'email' => $invitation->email, 'name' => $invitation->member_data['name'] ?? '']) }}" variant="outline" class="flex-1">
                                Sign Up
                            </flux:button>
                        </div>
                    </div>
                @endauth
            </flux:card>
        @endif
    </div>
</div>
