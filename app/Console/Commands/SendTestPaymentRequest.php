<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;

class SendTestPaymentRequest extends Command
{
    protected $signature = 'test:send-payment-request {payment_request_id}';

    protected $description = 'Send a test payment request notification';

    public function handle(): void
    {
        $paymentRequestId = $this->argument('payment_request_id');

        $paymentRequest = PaymentRequest::with(['member.user', 'group', 'cycle'])
            ->findOrFail($paymentRequestId);

        $this->info('=== Sending Payment Request ===');
        $this->newLine();

        $member = $paymentRequest->member;
        $memberUser = $member->user;
        $group = $paymentRequest->group;

        $this->line("Payment Request: {$paymentRequest->pr_code}");
        $this->line("To: {$memberUser->name} ({$memberUser->email})");
        $this->line('Amount: $'.($paymentRequest->amount_cents / 100));
        $this->line("Group: {$group->name}");
        $this->line("Channel: {$member->preferred_channel}");
        $this->newLine();

        // Show the payment link
        if ($paymentRequest->deep_link) {
            $this->info('=== Payment Link ===');
            $this->line($paymentRequest->deep_link);
            $this->newLine();
        }

        // Simulate sending notification
        $this->info('Simulating notification send...');

        // In real app, this would trigger:
        // - Push notification via FCM
        // - SMS via Twilio
        // - Email via SES

        $this->line('📱 Push Notification:');
        $this->line("   Title: Payment Request from {$group->name}");
        $this->line('   Body: Please pay $'.($paymentRequest->amount_cents / 100)." for {$group->name}");
        $this->line('   Action: Opens Cash App with pre-filled payment');
        $this->newLine();

        $this->line('📧 Email:');
        $this->line("   To: {$memberUser->email}");
        $this->line("   Subject: Payment Request - {$group->name}");
        $this->line("   Link: {$paymentRequest->deep_link}");
        $this->newLine();

        $this->line('💬 SMS:');
        $this->line("   To: {$memberUser->phone}");
        $this->line('   Message: Pay $'.($paymentRequest->amount_cents / 100)." for {$group->name}");
        $this->line("   Link: {$paymentRequest->deep_link}");
        $this->newLine();

        // Update status
        $paymentRequest->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->info('✓ Payment request marked as sent');
        $this->newLine();

        // Show clickable link
        $this->info('=== Test the Payment Link ===');
        $this->newLine();
        $this->line('Copy and paste this link in your browser or mobile:');
        $this->line("<fg=bright-cyan>{$paymentRequest->deep_link}</>");
        $this->newLine();

        $this->line('OR open Cash App directly with this command:');
        $amount = $paymentRequest->amount_cents / 100;
        $this->line("<fg=yellow>open 'cash://\$AbdallahTM/{$amount}'</>");
        $this->newLine();

        $this->info('Payment request sent successfully!');
    }
}
