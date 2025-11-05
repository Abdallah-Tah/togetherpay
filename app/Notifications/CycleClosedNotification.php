<?php

namespace App\Notifications;

use App\Models\BillingCycle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CycleClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BillingCycle $cycle,
        public array $metrics
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group = $this->cycle->group;
        $collectionRate = $this->metrics['collection_rate'];
        $totalAmount = number_format($this->metrics['total_amount'], 2);
        $collectedAmount = number_format($this->metrics['collected_amount'], 2);
        $outstanding = number_format($this->metrics['outstanding_amount'], 2);

        $message = (new MailMessage)
            ->subject("Billing Cycle Closed: {$group->name}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("The billing cycle for **{$group->name}** has been closed.")
            ->line("**Period:** {$this->cycle->period_start->format('M d')} - {$this->cycle->period_end->format('M d, Y')}")
            ->line('---')
            ->line('**Collection Summary:**')
            ->line("✓ Paid: {$this->metrics['paid_requests']} of {$this->metrics['total_requests']} members ({$collectionRate}%)")
            ->line("💵 Collected: \${$collectedAmount} of \${$totalAmount}");

        if ($this->metrics['outstanding_amount'] > 0) {
            $message->line("⚠️ Outstanding: \${$outstanding}");
        }

        return $message
            ->action('View Group Details', url("/groups/{$group->id}"))
            ->line('Thank you for using our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cycle_id' => $this->cycle->id,
            'group_id' => $this->cycle->group_id,
            'group_name' => $this->cycle->group->name,
            'metrics' => $this->metrics,
        ];
    }
}
