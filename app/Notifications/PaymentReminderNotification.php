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
        return ['mail', 'database'];
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
            'escalation' => '🚨 **URGENT** - Your payment is now '.$this->daysSinceDue.' days overdue.',
            'reminder' => 'This is a friendly reminder that your payment is '.($this->daysSinceDue > 0 ? "{$this->daysSinceDue} days overdue" : 'due soon').'.',
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
}
