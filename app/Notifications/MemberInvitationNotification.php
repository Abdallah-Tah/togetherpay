<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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

    public function toMail(object $notifiable): MailMessage
    {
        $invitationUrl = config('app.frontend_url')
            ? config('app.frontend_url') . "/register?token={$this->token}"
            : url("/register?token={$this->token}");

        return (new MailMessage)
            ->subject("You've been invited to join {$this->group->name} on TogetherPay")
            ->greeting('Welcome to TogetherPay!')
            ->line("{$this->invitedBy->name} has added you as a member to the group **{$this->group->name}**.")
            ->line("TogetherPay makes it easy to manage and pay your share of recurring bills. Here's what you can do:")
            ->line('• Receive payment requests automatically')
            ->line('• Pay via Venmo or Cash App')
            ->line('• Track your payment history')
            ->line('• Get notified when payments are due')
            ->action('Complete Registration', $invitationUrl)
            ->line('Click the button above to set up your account with a password and start receiving payment requests.')
            ->line("This invitation link will expire in 7 days.")
            ->salutation('Thanks,
The TogetherPay Team');
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
                ? config('app.frontend_url') . "/register?token={$this->token}"
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
