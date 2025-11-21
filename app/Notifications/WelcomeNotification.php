<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private bool $isInvitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(bool $isInvitation = false)
    {
        $this->isInvitation = $isInvitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $greeting = $this->isInvitation
            ? "Welcome to TogetherPay, {$notifiable->name}!"
            : "Welcome aboard, {$notifiable->name}!";

        $introLine = $this->isInvitation
            ? "Your account has been successfully created and you've been added to your group."
            : "Your TogetherPay account has been successfully created!";

        return (new MailMessage)
            ->subject('Welcome to TogetherPay')
            ->greeting($greeting)
            ->line($introLine)
            ->line('TogetherPay makes group bill splitting effortless with automatic payment requests and reminders.')
            ->line('')
            ->line('**What you can do:**')
            ->line('• View your groups and payment requests')
            ->line('• Receive automatic payment reminders')
            ->line('• Pay via Cash App or Venmo with one tap')
            ->line('• Track your payment history')
            ->line('')
            ->action('Open TogetherPay App', url('/'))
            ->line('')
            ->line('If you have any questions, feel free to reach out to our support team.')
            ->line('Thank you for joining TogetherPay!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'is_invitation' => $this->isInvitation,
        ];
    }
}
