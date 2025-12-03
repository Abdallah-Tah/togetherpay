<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyMemberPaymentSummary extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array $groupSummaries
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];

        // Only send if member has email notifications enabled
        if (($prefs['email'] ?? true) && ($prefs['weekly_summary'] ?? true)) {
            return ['mail'];
        }

        return [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $totalPending = 0;
        $totalConfirmed = 0;
        $needsAttention = [];

        foreach ($this->groupSummaries as $summary) {
            $totalPending += $summary['pending_count'];
            $totalConfirmed += $summary['confirmed_count'];

            if ($summary['needs_attention']) {
                $needsAttention[] = $summary;
            }
        }

        $message = (new MailMessage)
            ->subject('Your Weekly Payment Summary')
            ->greeting("Hey {$notifiable->name}! 👋")
            ->line("Here's your payment activity from the past week:");

        // Show summary by group
        foreach ($this->groupSummaries as $summary) {
            $message->line("**{$summary['group_name']}**");

            if ($summary['confirmed_count'] > 0) {
                $message->line("✅ Confirmed: \${$summary['confirmed_amount']} ({$summary['confirmed_count']} payment".($summary['confirmed_count'] === 1 ? '' : 's').')');
            }

            if ($summary['pending_count'] > 0) {
                $message->line("⏳ Pending confirmation: \${$summary['pending_amount']} ({$summary['pending_count']} payment".($summary['pending_count'] === 1 ? '' : 's').')');
            }

            if ($summary['auto_confirm_soon']) {
                $message->line("⚡ Auto-confirming soon: {$summary['auto_confirm_soon']} payment".($summary['auto_confirm_soon'] === 1 ? '' : 's'));
            }

            if ($summary['needs_attention']) {
                $message->line("💬 **Needs attention:** {$summary['attention_message']}");
            }

            $message->line(''); // Empty line between groups
        }

        // Add action button if there are pending items
        if ($totalPending > 0 || count($needsAttention) > 0) {
            $message->action('Review Payments', url('/dashboard'));
        }

        $message->line('Thank you for using TogetherPay! 💙');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'group_summaries' => $this->groupSummaries,
        ];
    }
}
