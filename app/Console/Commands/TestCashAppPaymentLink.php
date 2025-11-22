<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Console\Command;

class TestCashAppPaymentLink extends Command
{
    protected $signature = 'test:cashapp-link';

    protected $description = 'Generate Cash App payment request link for testing';

    public function handle(): void
    {
        $this->info('=== Cash App Payment Request Test ===');
        $this->info('From: Owner ($IkramBarkad) → To: Member ($AbdallahTM)');
        $this->info('Amount: $5.00');
        $this->newLine();

        // Create owner
        $owner = User::factory()->create([
            'name' => 'Ikram Barkad',
            'email' => 'ikram@test.com',
            'phone' => '+12065551234',
        ]);

        // Create group
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Netflix Group',
            'description' => 'Monthly Netflix subscription split',
            'total_amount_cents' => 500,
            'billing_mode' => 'manual',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        // Create member (YOU)
        $memberUser = User::factory()->create([
            'name' => 'Abdallah Mohamed',
            'email' => 'abdallah@test.com',
            'phone' => '+12065559999',
        ]);

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$AbdallahTM',
            'share_type' => 'AMOUNT',
            'share_value' => 5.00,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Create cycle
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => 500,
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        // Generate Cash App deep link with owner as recipient
        $amount = 5.00;
        $note = urlencode("Netflix - {$cycle->period_start->format('M Y')} - From {$owner->name}");

        // The payment goes TO the owner's Cash App ($IkramBarkad)
        $deepLink = "https://cash.app/\$IkramBarkad/{$amount}?note={$note}";

        // Create payment request
        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 500,
            'method_hint' => 'CASH_APP',
            'pr_code' => 'PR-'.strtoupper(substr(md5(uniqid()), 0, 8)),
            'note' => "Netflix - {$cycle->period_start->format('M Y')}",
            'deep_link' => $deepLink,
            'status' => 'sent',
            'initiated_at' => now(),
            'sent_at' => now(),
        ]);

        $this->info('✓ Payment request created');
        $this->newLine();

        $this->info('=== PAYMENT REQUEST DETAILS ===');
        $this->newLine();
        $this->line("<fg=yellow>PR Code:</> {$paymentRequest->pr_code}");
        $this->line("<fg=yellow>From (Owner):</> {$owner->name} (\$IkramBarkad)");
        $this->line("<fg=yellow>To (Member):</> {$memberUser->name} (\$AbdallahTM)");
        $this->line('<fg=yellow>Amount:</> $5.00');
        $this->newLine();

        $this->info('=== CASH APP PAYMENT LINK ===');
        $this->newLine();
        $this->line('Click this link on your phone to pay:');
        $this->line("<fg=bright-cyan>{$deepLink}</>");
        $this->newLine();

        $this->line('Or scan this QR code (if generated):');
        $this->line("cash://\$IkramBarkad/{$amount}");
        $this->newLine();

        $this->info('=== HOW IT WORKS ===');
        $this->line('1. YOU ($AbdallahTM) click the link above');
        $this->line('2. Cash App opens with payment screen');
        $this->line('3. Recipient: $IkramBarkad (the owner)');
        $this->line('4. Amount: $5.00');
        $this->line('5. Note: Netflix payment');
        $this->line('6. Complete payment in Cash App');
        $this->line('7. Mark as paid in the app');
        $this->line('8. Owner ($IkramBarkad) confirms receipt');
        $this->newLine();

        $this->info('=== TEST DATA IDS ===');
        $this->line("Owner ID: {$owner->id}");
        $this->line("Member ID: {$member->id}");
        $this->line("Group ID: {$group->id}");
        $this->line("Cycle ID: {$cycle->id}");
        $this->line("Payment Request ID: {$paymentRequest->id}");
        $this->newLine();

        $this->info('✓ Test completed! Click the link above to test the payment flow.');
    }
}
