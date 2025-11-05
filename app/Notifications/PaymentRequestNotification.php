<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PaymentRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (! empty($notifiable->phone) && config('services.twilio.enabled', false)) {
            $channels[] = TwilioChannel::class;
        }

        // Add push notification if user has registered devices with FCM tokens
        if ($notifiable->devices()->whereNotNull('fcm_token')->exists()) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $dueDate = $cycle?->due_at?->timezone($group->timezone ?? config('app.timezone'))
            ?->format('M d, Y') ?? now()->format('M d, Y');
        $payLink = url("/pay/{$this->paymentRequest->id}");

        $message = (new MailMessage)
            ->subject('💰 Payment Due'.($group ? ": {$group->name}" : ''))
            ->greeting("Hi {$notifiable->name}! 👋")
            ->line("**💵 Amount:** \${$amount}")
            ->line("**📅 Due Date:** {$dueDate}")
            ->line('')
            ->action('Pay Now', $payLink)
            ->line('')
            ->line('After paying, click "I\'ve Sent the Payment" on the payment page.')
            ->line('')
            ->line('Questions? Reply to this email.')
            ->salutation('Thank you!');

        if ($group) {
            $message->line("Your share for **{$group->name}** is now due.")->line('');
        }

        return $message;
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $dueDate = $cycle?->due_at?->timezone($group->timezone ?? config('app.timezone'))
            ?->format('M d, Y') ?? now()->format('M d, Y');

        $deepLink = $this->paymentRequest->deep_link ?? url("/pay/{$this->paymentRequest->id}");

        $lines = [
            '💰 '.($group?->name ?? 'Payment due'),
            "Amount: \${$amount}",
            "Due: {$dueDate}",
            "Pay: {$deepLink}",
        ];

        $message = implode("\n", $lines);

        $twilioMessage = (new TwilioSmsMessage)
            ->content($message);

        if ($messagingService = config('services.twilio.messaging_service_sid')) {
            $twilioMessage->messagingServiceSid($messagingService);
        } elseif ($from = config('services.twilio.from')) {
            $twilioMessage->from($from);
        }

        return $twilioMessage;
    }

    /**
     * FCM push notification payload with sound and deep link
     */
    public function toFcm(object $notifiable): array
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);
        $dueDate = $cycle?->due_at?->timezone($group->timezone ?? config('app.timezone'))
            ?->format('M d, Y') ?? now()->format('M d, Y');

        $deepLink = $this->paymentRequest->deep_link
            ?? $this->paymentRequest->metadata['link'] ?? '';

        return [
            'notification' => [
                'title' => '💰 Payment Due'.($group ? ": {$group->name}" : ''),
                'body' => "Your share of \${$amount} is due on {$dueDate}",
                'sound' => 'default', // Enable notification sound
            ],
            'data' => [
                'pr_code' => $this->paymentRequest->code,
                'deep_link' => $deepLink,
                'amount' => $amount,
                'group_name' => $group?->name ?? '',
                'due_date' => $dueDate,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'togetherpay_default',
                    'sound' => 'default',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                    'default_light_settings' => true,
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                        'content-available' => 1,
                    ],
                ],
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $group = $this->paymentRequest->cycle?->group ?? $this->paymentRequest->group;

        return [
            'payment_request_id' => $this->paymentRequest->id,
            'cycle_id' => $this->paymentRequest->cycle_id,
            'group_name' => $group?->name ?? '',
            'amount_cents' => $this->paymentRequest->amount_cents,
            'due_at' => $this->paymentRequest->cycle?->due_at,
            'link' => url("/pay/{$this->paymentRequest->id}"),
        ];
    }
}
