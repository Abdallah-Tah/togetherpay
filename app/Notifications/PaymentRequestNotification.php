<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // Add push notification if enabled (future feature)
        // if (config('payment_requests.features.push_notifications')) {
        //     $channels[] = 'fcm';
        // }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle->group;
        $amount = number_format($this->paymentRequest->amount, 2);
        $dueDate = $cycle->due_at->format('F j, Y');

        return (new MailMessage)
            ->subject("Payment Request: {$group->name}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("You have a new payment request for **{$group->name}**.")
            ->line("**Amount:** \${$amount}")
            ->line("**Due Date:** {$dueDate}")
            ->line("**Period:** {$cycle->period_start->format('M d')} - {$cycle->period_end->format('M d, Y')}")
            ->action('Pay Now', $this->paymentRequest->link)
            ->line('Thank you for being part of this group!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_request_id' => $this->paymentRequest->id,
            'cycle_id' => $this->paymentRequest->cycle_id,
            'group_name' => $this->paymentRequest->cycle->group->name,
            'amount' => $this->paymentRequest->amount,
            'due_at' => $this->paymentRequest->cycle->due_at,
            'link' => $this->paymentRequest->link,
        ];
    }
}
