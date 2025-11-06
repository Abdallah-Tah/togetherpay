<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PaymentRequest $paymentRequest,
        public string $reminderType,
        public int $daysSinceDue
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        $prefs = $notifiable->notification_preferences ?? [];

        // Check if payment reminders are enabled
        if (!($prefs['payment_reminders'] ?? true)) {
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
        $cycle = $this->paymentRequest->cycle;
        $group = $cycle->group;
        $amount = number_format($this->paymentRequest->amount, 2);

        $message = (new MailMessage);

        if ($this->reminderType === 'owner_escalation') {
            return $message
                ->subject("Payment Overdue: {$group->name}")
                ->greeting("Hi {$notifiable->name},")
                ->line("A payment in your group **{$group->name}** is {$this->daysSinceDue} days overdue.")
                ->line("**Member:** {$this->paymentRequest->member->user->name}")
                ->line("**Amount:** \${$amount}")
                ->line('You may want to reach out to the member directly.')
                ->action('View Group', url("/groups/{$group->id}"));
        }

        $subject = match ($this->reminderType) {
            'reminder' => "Payment Reminder: {$group->name}",
            'escalation' => "URGENT: Payment Overdue - {$group->name}",
            default => "Payment Due: {$group->name}",
        };

        $urgency = match ($this->reminderType) {
            'escalation' => '🚨 **URGENT** - Your payment is now ' . $this->daysSinceDue . ' days overdue.',
            'reminder' => 'This is a friendly reminder that your payment is ' . ($this->daysSinceDue > 0 ? "{$this->daysSinceDue} days overdue" : 'due soon') . '.',
            default => 'Your payment is due.',
        };

        return $message
            ->subject($subject)
            ->greeting("Hi {$notifiable->name}!")
            ->line($urgency)
            ->line("**Group:** {$group->name}")
            ->line("**Amount:** \${$amount}")
            ->line("**Due Date:** {$cycle->due_at->format('F j, Y')}")
            ->action('Pay Now', $this->paymentRequest->link)
            ->line('Please complete your payment as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_request_id' => $this->paymentRequest->id,
            'reminder_type' => $this->reminderType,
            'days_since_due' => $this->daysSinceDue,
            'amount' => $this->paymentRequest->amount,
            'group_name' => $this->paymentRequest->cycle->group->name,
        ];
    }

    /**
     * FCM payload for reminders (optional push channel)
     */
    public function toFcm(object $notifiable): array
    {
        $group = $this->paymentRequest->cycle->group;
        $amount = number_format($this->paymentRequest->amount, 2);
        $dueDate = $this->paymentRequest->cycle->due_at?->format('M d, Y');

        $title = match ($this->reminderType) {
            'owner_escalation' => '⚠️ Overdue Payment Escalated',
            'escalation' => '🚨 Payment Overdue',
            'reminder' => '⏰ Payment Reminder',
            default => 'Payment Due',
        };

        $body = match ($this->reminderType) {
            'owner_escalation' => "Member overdue {$this->daysSinceDue}d • $${amount} • {$group->name}",
            'escalation' => "Overdue {$this->daysSinceDue}d • $${amount} • Due {$dueDate}",
            'reminder' => "{$group->name}: $${amount} due {$dueDate}",
            default => "{$group->name}: $${amount} due {$dueDate}",
        };

        return [
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => [
                'payment_request_id' => (string) $this->paymentRequest->id,
                'reminder_type' => $this->reminderType,
                'days_since_due' => (string) $this->daysSinceDue,
                'amount' => $amount,
                'group_name' => $group->name,
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
}
