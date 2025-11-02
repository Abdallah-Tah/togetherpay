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

        // SMS support available - install: composer require laravel-notification-channels/twilio
        // if ($notifiable->phone && config('services.twilio.enabled', false)) {
        //     $channels[] = TwilioChannel::class;
        // }

        // Add push notification if FCM token exists
        // if ($notifiable->fcm_token) {
        //     $channels[] = 'fcm';
        // }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $dueDate = $cycle->due_at->format('M d, Y');
        $payLink = url("/pay/{$this->paymentRequest->id}");

        return (new MailMessage)
            ->subject("💰 Payment Due: {$group->name}")
            ->greeting("Hi {$notifiable->name}! 👋")
            ->line("Your share for **{$group->name}** is now due.")
            ->line('')
            ->line("**💵 Amount:** \${$amount}")
            ->line("**📅 Due Date:** {$dueDate}")
            ->line('')
            ->action('Pay Now', $payLink)
            ->line('')
            ->line('After paying, click "I\'ve Sent the Payment" on the payment page.')
            ->line('')
            ->line('Questions? Reply to this email.')
            ->salutation('Thank you!');
    }

    /**
     * SMS notification via Twilio (optional)
     *
     * To enable SMS:
     * 1. Install: composer require laravel-notification-channels/twilio
     * 2. Add to .env:
     *    TWILIO_ENABLED=true
     *    TWILIO_SID=your_sid
     *    TWILIO_TOKEN=your_token
     *    TWILIO_FROM=+1234567890
     * 3. Uncomment this method
     */
    // public function toTwilio(object $notifiable): TwilioSmsMessage
    // {
    //     $cycle = $this->paymentRequest->cycle;
    //     $group = $cycle->group;
    //     $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
    //     $dueDate = $cycle->due_at->format('M d, Y');
    //     $payLink = url("/pay/{$this->paymentRequest->id}");
    //
    //     $message = "💰 Payment Due: {$group->name}\n\n";
    //     $message .= "Amount: \${$amount}\n";
    //     $message .= "Due: {$dueDate}\n\n";
    //     $message .= "Pay now: {$payLink}\n\n";
    //     $message .= "Questions? Reply to this message.";
    //
    //     return (new TwilioSmsMessage())
    //         ->content($message);
    // }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_request_id' => $this->paymentRequest->id,
            'cycle_id' => $this->paymentRequest->cycle_id,
            'group_name' => $this->paymentRequest->cycle->group->name,
            'amount_cents' => $this->paymentRequest->amount_cents,
            'due_at' => $this->paymentRequest->cycle->due_at,
            'link' => url("/pay/{$this->paymentRequest->id}"),
        ];
    }
}
