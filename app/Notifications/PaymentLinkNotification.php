<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

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
        $channels = ['mail'];

        if (!empty($notifiable->phone) && config('services.twilio.enabled', false)) {
            $channels[] = TwilioChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $timezone = $group?->timezone ?? config('app.timezone');
        $dueDate = $cycle?->due_at?->timezone($timezone)
            ?->format('M d, Y') ?? now()->format('M d, Y');

        return (new MailMessage)
            ->subject("Payment Due: \${$amount}" . ($group ? " - {$group->name}" : ''))
            ->greeting("Hi {$notifiable->name}!")
            ->line('Your payment is due.')
            ->when($group, fn (MailMessage $message) => $message->line("**Group:** {$group->name}"))
            ->line("**Amount:** \${$amount}")
            ->line("**Due Date:** {$dueDate}")
            ->action('Pay Now', $this->link)
            ->line('Click the button above to open your payment app.')
            ->line('Questions? Contact the group owner.');
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $deepLink = $this->link;
        $groupName = $group?->name ? "{$group->name} " : '';

        $message = "💰 Payment due: \${$amount} {$groupName}- Pay now: {$deepLink}";

        $twilioMessage = (new TwilioSmsMessage())
            ->content($message);

        if ($messagingService = config('services.twilio.messaging_service_sid')) {
            $twilioMessage->messagingServiceSid($messagingService);
        } elseif ($from = config('services.twilio.from')) {
            $twilioMessage->from($from);
        }

        return $twilioMessage;
    }
}
