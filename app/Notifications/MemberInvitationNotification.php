<?php

namespace App\Notifications;

use App\Mail\MemberInvitationMail;
use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MemberInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $invitedBy,
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        // Check notification preferences
        $preferences = is_string($notifiable->notification_preferences)
            ? json_decode($notifiable->notification_preferences, true)
            : ($notifiable->notification_preferences ?? []);

        if (($preferences['email'] ?? true) && $notifiable->email) {
            $channels[] = 'mail';
        }

        if (($preferences['push'] ?? true) && $notifiable->fcm_token) {
            $channels[] = \App\Channels\FcmChannel::class;
        }

        // Always send to database
        $channels[] = 'database';

        return array_unique($channels);
    }

    public function toMail(object $notifiable): MemberInvitationMail
    {
        return new MemberInvitationMail($this->group, $this->invitedBy, $this->token);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'member_invitation',
            'title' => 'Welcome to TogetherPay!',
            'message' => "{$this->invitedBy->name} has added you to {$this->group->name}. Complete your registration to start receiving payment requests.",
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'invited_by' => $this->invitedBy->name,
            'token' => $this->token,
            'action_url' => config('app.frontend_url')
                ? config('app.frontend_url')."/register?token={$this->token}"
                : url("/register?token={$this->token}"),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Welcome to TogetherPay!',
            'body' => "{$this->invitedBy->name} added you to {$this->group->name}. Complete your registration to get started.",
            'data' => [
                'type' => 'member_invitation',
                'group_id' => (string) $this->group->id,
                'token' => $this->token,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }
}
