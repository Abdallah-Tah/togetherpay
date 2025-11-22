<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Console\Command;

class TestCashAppPayment extends Command
{
    protected $signature = 'test:cashapp-payment {cash_tag} {member_email} {member_name?} {amount=5}';

    protected $description = 'Create a direct Cash App payment request using deep-link';

    public function handle(): void
    {
        $cashTag = $this->argument('cash_tag');
        $memberEmail = $this->argument('member_email');
        $memberName = $this->argument('member_name') ?: 'Test Member';
        $amount = (float) $this->argument('amount');

        // Normalize cash tag - remove/add $ as needed
        $cashTag = trim($cashTag);
        $cashTag = ltrim($cashTag, '$');
        if (empty($cashTag)) {
            $this->error('Cash App tag cannot be empty!');

            return;
        }
        $cashTag = '$'.$cashTag;

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║        Creating Direct Cash App Payment Request                ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Find owner
        $owner = User::whereNotNull('square_merchant_id')->first();
        if (! $owner) {
            $owner = User::first();
        }

        $this->line("✓ Using owner: {$owner->name}");
        $this->newLine();

        // Create group
        $this->info('Step 1: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Cash App Test - '.now()->format('H:i:s'),
            'description' => 'Testing direct Cash App payment requests',
            'total_amount_cents' => (int) ($amount * 100),
            'billing_mode' => 'manual',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group created: {$group->name}");
        $this->newLine();

        // Create member
        $this->info('Step 2: Setting up member...');
        $memberUser = User::firstOrCreate(
            ['email' => $memberEmail],
            [
                'name' => $memberName,
                'phone' => '+12065551000',
                'password' => bcrypt('password'),
            ]
        );

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => $cashTag,
            'cash_phone' => '+12065551000',
            'cash_email' => $memberEmail,
            'share_type' => 'AMOUNT',
            'share_value' => $amount,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->line('✓ Member created');
        $this->line("  Name: {$memberName}");
        $this->line("  Email: {$memberEmail}");
        $this->line("  Cash App: {$cashTag}");
        $this->newLine();

        // Create billing cycle
        $this->info('Step 3: Creating billing cycle...');
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => (int) ($amount * 100),
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        $this->line('✓ Billing cycle created');
        $this->newLine();

        // Generate Cash App deep-link
        $this->info('Step 4: Generating Cash App deep-link...');

        $cleanTag = ltrim($cashTag, '$');
        $deepLink = sprintf(
            'cashapp://send?username=%s&amount=%.2f&note=%s',
            urlencode($cleanTag),
            $amount,
            urlencode("Group payment - {$group->name}")
        );

        // Also create web link
        $webLink = sprintf(
            'https://cash.app/%s/%s',
            $cleanTag,
            $amount
        );

        // Create payment request record
        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => (int) ($amount * 100),
            'method_hint' => 'CASH_APP',
            'status' => 'sent',
            'deep_link' => $deepLink,
            'sent_at' => now(),
        ]);

        $this->line('✓ Payment request created');
        $this->line("  PR ID: {$paymentRequest->id}");
        $this->newLine();

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                    ✓ READY TO PAY!                             ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line('<fg=yellow>Payment Details:</>');
        $this->line("  Recipient: {$cashTag}");
        $this->line("  Amount: \${$amount}");
        $this->line("  Note: Group payment - {$group->name}");
        $this->newLine();

        $this->line('<fg=green>💳 How to Send Payment:</>');
        $this->newLine();

        $this->line('<fg=cyan>Option 1: Mobile (Recommended)</>');
        $this->line('  1. Open this URL on your phone:');
        $this->line("  <fg=bright-blue>{$deepLink}</>");
        $this->line('  2. Cash App will open with payment pre-filled');
        $this->line('  3. Swipe to confirm and send');
        $this->newLine();

        $this->line('<fg=cyan>Option 2: Web Browser</>');
        $this->line('  1. Open this link:');
        $this->line("  <fg=bright-blue>{$webLink}</>");
        $this->line("  2. Or open Cash App and search for {$cashTag}");
        $this->line("  3. Enter amount: \${$amount}");
        $this->line('  4. Swipe to confirm');
        $this->newLine();

        $this->line('<fg=cyan>Option 3: Manual in Cash App</>');
        $this->line('  1. Open Cash App');
        $this->line('  2. Tap the $$ button (send money)');
        $this->line("  3. Search for: {$cashTag}");
        $this->line("  4. Enter amount: \${$amount}");
        $this->line('  5. Add note: Group payment');
        $this->line('  6. Swipe to send');
        $this->newLine();

        $this->line('<fg=yellow>📊 Test Data:</>');
        $this->line("  Owner: {$owner->name}");
        $this->line("  Group ID: {$group->id}");
        $this->line("  Member: {$memberName}");
        $this->line("  Payment Request ID: {$paymentRequest->id}");
        $this->line("  Amount: \${$amount}");
        $this->newLine();

        $this->info('Next Steps:');
        $this->line('  1. Click/copy one of the links above');
        $this->line('  2. Open it on your phone (Cash App must be installed)');
        $this->line('  3. Confirm the payment details');
        $this->line('  4. Swipe to send the payment');
        $this->line('  5. Payment will be marked as completed');
        $this->newLine();

        $this->line('<fg=cyan>Track payment status:</>');
        $this->line("  Group ID: {$group->id}");
        $this->line("  DB: payment_requests table, ID {$paymentRequest->id}");
        $this->newLine();
    }
}
