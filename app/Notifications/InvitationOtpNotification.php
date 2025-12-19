<?php

namespace App\Notifications;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GroupInvitation $invitation,
        public string $code,
        public int $expiresMinutes = 10,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $groupName = $this->invitation->group?->name ?? 'TogetherPay';

        return (new MailMessage)
            ->subject("Your TogetherPay code ({$this->expiresMinutes} min)")
            ->greeting('Your verification code')
            ->line("Use this code to accept your invite for **{$groupName}**.")
            ->line("**{$this->code}**")
            ->line("This code expires in {$this->expiresMinutes} minutes.")
            ->line('If you did not request this code, you can ignore this email.');
    }
}

