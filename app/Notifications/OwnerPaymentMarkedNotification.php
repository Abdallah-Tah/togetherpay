<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerPaymentMarkedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PaymentRequest $paymentRequest
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always store in-app

        $prefs = $notifiable->notification_preferences ?? [];

        // Email if enabled
        if (($prefs['email'] ?? true) && ($prefs['payment_confirmations'] ?? true)) {
            $channels[] = 'mail';
        }

        // Push notification if devices exist
        if ($notifiable->devices()->whereNotNull('fcm_token')->exists()) {
            $channels[] = \App\Channels\FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $member = $this->paymentRequest->member;
        $memberName = $member?->user?->name ?? 'A member';
        $group = $this->paymentRequest->cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        // Calculate hours until auto-confirm
        $hoursUntilAutoConfirm = $this->paymentRequest->auto_confirm_at
            ? now()->diffInHours($this->paymentRequest->auto_confirm_at, false)
            : 24;

        $autoConfirmText = $hoursUntilAutoConfirm <= 1
            ? 'in about 1 hour'
            : "in about {$hoursUntilAutoConfirm} hours";

        return (new MailMessage)
            ->subject("💰 Payment Received: {$memberName}")
            ->greeting("Hey {$notifiable->name}! 👋")
            ->line("**{$memberName}** marked their payment as sent for **{$group?->name}**")
            ->line("Amount: \${$amount}")
            ->line("This payment will automatically be confirmed {$autoConfirmText} unless you report an issue.")
            ->action('Confirm Now', url("/api/v1/requests/by-code/{$this->paymentRequest->pr_code}/confirm"))
            ->line('If you haven\'t received this payment yet, tap "Something doesn\'t look right" in the app.')
            ->line('Thank you for using TogetherPay! 💙');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $member = $this->paymentRequest->member;
        $memberName = $member?->user?->name ?? 'A member';
        $group = $this->paymentRequest->cycle?->group ?? $this->paymentRequest->group;
        $amount = number_format($this->paymentRequest->amount_cents / 100, 2);

        $hoursUntilAutoConfirm = $this->paymentRequest->auto_confirm_at
            ? now()->diffInHours($this->paymentRequest->auto_confirm_at, false)
            : 24;

        return [
            'notification' => [
                'title' => "💰 {$memberName} paid \${$amount}",
                'body' => "Auto-confirming in ~{$hoursUntilAutoConfirm}h. Tap to review.",
            ],
            'data' => [
                'type' => 'payment_marked',
                'pr_code' => $this->paymentRequest->pr_code,
                'amount' => $amount,
                'member_name' => $memberName,
                'group_name' => $group?->name ?? '',
                'auto_confirm_hours' => (string) $hoursUntilAutoConfirm,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'togetherpay_payments',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $member = $this->paymentRequest->member;
        $group = $this->paymentRequest->cycle?->group ?? $this->paymentRequest->group;

        return [
            'type' => 'payment_marked',
            'payment_request_id' => $this->paymentRequest->id,
            'pr_code' => $this->paymentRequest->pr_code,
            'member_id' => $member?->id,
            'member_name' => $member?->user?->name,
            'group_name' => $group?->name,
            'amount_cents' => $this->paymentRequest->amount_cents,
            'auto_confirm_at' => $this->paymentRequest->auto_confirm_at?->toIso8601String(),
        ];
    }
}
