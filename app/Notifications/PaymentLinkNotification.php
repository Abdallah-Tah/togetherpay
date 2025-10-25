<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
        public string $link
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['mail']; // Always send email

        // Add SMS if phone number exists
        if ($notifiable->phone) {
            $channels[] = 'vonage'; // or 'twilio'
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $group = $this->paymentRequest->cycle->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        return (new MailMessage)
            ->subject("Payment Due: \${$amount} - {$group->name}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your payment for **{$group->name}** is due.")
            ->line("**Amount:** \${$amount}")
            ->line("**Due Date:** {$this->paymentRequest->due_at->format('M d, Y')}")
            ->action('Pay Now', $this->link)
            ->line('Click the button above to open your payment app.')
            ->line('Questions? Contact the group owner.');
    }

    /**
     * Get the Vonage/SMS representation of the notification.
     */
    public function toVonage($notifiable): VonageMessage
    {
        $group = $this->paymentRequest->cycle->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        return (new VonageMessage)
            ->content("💰 Payment due: \${$amount} for {$group->name}. Pay now: {$this->link}");
    }
}
