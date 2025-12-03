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
        $channels = [];
        $prefs = $notifiable->notification_preferences ?? [];

        // Email channel
        if (($prefs['email'] ?? true) && ($prefs['payment_requests'] ?? true)) {
            $channels[] = 'mail';
        }

        // Database channel (always enabled for in-app notifications)
        $channels[] = 'database';

        // SMS channel
        if (
            ! empty($notifiable->phone) &&
            config('services.twilio.enabled', false) &&
            ($prefs['sms'] ?? false) &&
            ($prefs['payment_requests'] ?? true)
        ) {
            $channels[] = TwilioChannel::class;
        }

        // Push notification channel
        if (
            $notifiable->devices()->whereNotNull('fcm_token')->exists() &&
            ($prefs['push'] ?? true) &&
            ($prefs['payment_requests'] ?? true)
        ) {
            $channels[] = \App\Channels\FcmChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        // Fix: Handle null due_at safely
        $dueDate = 'N/A';
        if ($cycle?->due_at) {
            $dueDate = $cycle->due_at->timezone($group->timezone ?? config('app.timezone'))
                ->format('M d, Y');
        }

        $payLink = url("/pay/{$this->paymentRequest->id}");

        return (new MailMessage)
            ->subject('Payment Due'.($group ? ": {$group->name}" : ''))
            ->markdown('emails.payment-request', [
                'notifiable' => $notifiable,
                'paymentRequest' => $this->paymentRequest,
                'group' => $group,
                'amount' => $amount,
                'dueDate' => $dueDate,
                'payLink' => $payLink,
            ]);
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
