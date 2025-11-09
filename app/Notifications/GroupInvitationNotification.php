<?php

namespace App\Notifications;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GroupInvitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->invitation->loadMissing(['group', 'invitedBy']);
        $group = $invitation->group;
        $invitedBy = $invitation->invitedBy;

        $invitationUrl = config('app.frontend_url')
            ? config('app.frontend_url')."/accept-invitation/{$invitation->token}"
            : url("/accept-invitation/{$invitation->token}");

        return (new MailMessage)
            ->subject("You've been invited to join {$group->name}")
            ->greeting('Hi there!')
            ->line("{$invitedBy->name} has invited you to join their group **{$group->name}** on TogetherPay.")
            ->line("TogetherPay makes it easy to split recurring bills with your group. You'll be able to:")
            ->line('• Receive payment requests automatically')
            ->line('• Pay via Venmo or Cash App')
            ->line('• Track your payment history')
            ->action('Accept Invitation', $invitationUrl)
            ->line("This invitation will expire on {$invitation->expires_at->format('F j, Y')}.")
            ->line("If you don't have a TogetherPay account, you'll be prompted to create one when you accept the invitation.");
    }
}
