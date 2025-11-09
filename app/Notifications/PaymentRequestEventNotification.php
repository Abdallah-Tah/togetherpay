<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Unified notification for payment request lifecycle events.
 * Events: created (sent to owner), member_marked (sent to owner), owner_confirmed (sent to member)
 */
class PaymentRequestEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
        public string $event
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        $prefs = $notifiable->notification_preferences ?? [];

        // Determine which notification type this falls under
        $typeEnabled = match ($this->event) {
            'created' => $prefs['payment_requests'] ?? true,
            'member_marked', 'owner_confirmed' => $prefs['payment_confirmations'] ?? true,
            default => true,
        };

        if (! $typeEnabled) {
            return ['database']; // Always log to database
        }

        // Email channel
        if ($prefs['email'] ?? true) {
            $channels[] = 'mail';
        }

        // Database channel (always enabled)
        $channels[] = 'database';

        // Push notification channel
        if (
            $notifiable->devices()->whereNotNull('fcm_token')->exists() &&
            ($prefs['push'] ?? true)
        ) {
            $channels[] = \App\Channels\FcmChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->paymentRequest->loadMissing(['member.user', 'cycle.group', 'group.owner']);
        $group = $pr->cycle?->group ?? $pr->group;
        $memberUser = $pr->member?->user;
        $amount = number_format($pr->amount_cents / 100, 2);
        $dueDate = $pr->cycle?->due_at?->timezone($group->timezone ?? config('app.timezone'))?->format('M d, Y');

        $mail = new MailMessage;

        $subject = match ($this->event) {
            'created' => "Payment Request Sent: {$group?->name}",
            'member_marked' => "Payment Marked Sent: {$group?->name}",
            'owner_confirmed' => "Payment Confirmed: {$group?->name}",
            default => 'Payment Update',
        };

        $mail->subject($subject)
            ->greeting("Hi {$notifiable->name}!")
            ->line("Group: **{$group?->name}**")
            ->line("Amount: **\${$amount}**");

        if ($dueDate) {
            $mail->line("Due Date: {$dueDate}");
        }

        $mail->line(match ($this->event) {
            'created' => "A payment request was sent to **{$memberUser?->name}**.",
            'member_marked' => "**{$memberUser?->name}** marked their payment as sent.",
            'owner_confirmed' => 'Your payment was confirmed by the group owner.',
            default => 'Payment status updated.',
        });

        $mail->action('View Request', url('/pay/'.$pr->id));

        return $mail;
    }

    public function toFcm(object $notifiable): array
    {
        $pr = $this->paymentRequest->loadMissing(['member.user', 'cycle.group', 'group.owner']);
        $group = $pr->cycle?->group ?? $pr->group;
        $memberUser = $pr->member?->user;
        $amount = number_format($pr->amount_cents / 100, 2);
        $dueDate = $pr->cycle?->due_at?->timezone($group->timezone ?? config('app.timezone'))?->format('M d, Y');

        $title = match ($this->event) {
            'created' => '💸 Request Sent',
            'member_marked' => '📤 Payment Marked Sent',
            'owner_confirmed' => '✅ Payment Confirmed',
            default => 'Payment Update',
        };

        $body = match ($this->event) {
            'created' => "Sent to {$memberUser?->name}: \${$amount}".($dueDate ? " • Due {$dueDate}" : ''),
            'member_marked' => "{$memberUser?->name} marked \${$amount} as sent".($dueDate ? " • Due {$dueDate}" : ''),
            'owner_confirmed' => "Confirmed: \${$amount}".($group?->name ? " • {$group->name}" : ''),
            default => "Update: \${$amount}",
        };

        return [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'event' => $this->event,
                'payment_request_id' => (string) $pr->id,
                'pr_code' => $pr->pr_code,
                'group_name' => $group?->name ?? '',
                'amount' => $amount,
                'due_date' => $dueDate ?? '',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'togetherpay_default',
                    'sound' => 'default',
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
        $pr = $this->paymentRequest;

        return [
            'payment_request_id' => $pr->id,
            'event' => $this->event,
            'status' => $pr->status,
            'amount_cents' => $pr->amount_cents,
            'pr_code' => $pr->pr_code,
        ];
    }
}
