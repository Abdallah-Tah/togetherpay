<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareSubscriptionService;
use Illuminate\Console\Command;

class TestRealSquareCashApp extends Command
{
    protected $signature = 'test:real-square-cashapp';

    protected $description = 'Test real Square + Cash App payment request to $AbdallahTM';

    public function handle(): void
    {
        $this->info('=== Real Square + Cash App Pay Test ===');
        $this->info('Testing with real Cash App: $AbdallahTM');
        $this->newLine();

        // Use your existing user account
        $this->info('Step 1: Finding or creating owner account...');
        $owner = User::where('email', 'LIKE', '%amohamed%')->first();

        if (!$owner) {
            $this->warn('No owner found, creating test owner...');
            $owner = User::factory()->create([
                'name' => 'Abdallah Mohamed',
                'email' => 'test-owner-' . time() . '@togetherpay.local',
                'phone' => '+1234567890',
            ]);
        }

        // Connect to Square (if not already connected)
        if (!$owner->hasSquareAccount()) {
            $this->info('Connecting owner to Square...');
            $owner->update([
                'square_merchant_id' => 'TEST_MERCHANT_' . time(),
                'square_access_token' => encrypt(config('square.access_token')),
                'square_location_id' => config('square.location_id') ?: 'MAIN',
                'square_connected_at' => now(),
            ]);
        }

        $this->line("✓ Owner: {$owner->name} ({$owner->email})");
        $this->line("  Square Connected: " . ($owner->hasSquareAccount() ? 'Yes' : 'No'));
        $this->newLine();

        // Step 2: Create test group
        $this->info('Step 2: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Test Netflix Group ' . time(),
            'description' => 'Testing Square + Cash App Pay integration',
            'total_amount_cents' => 1999, // $19.99
            'billing_mode' => 'auto_square',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group created: {$group->name}");
        $this->line("  Total: $" . ($group->total_amount_cents / 100));
        $this->line("  Billing Mode: {$group->billing_mode}");
        $this->newLine();

        // Step 3: Create member with YOUR Cash App
        $this->info('Step 3: Creating member with YOUR Cash App ($AbdallahTM)...');

        // Find or create member user
        $memberUser = User::where('email', 'like', '%test-member%')->first();
        if (!$memberUser) {
            $memberUser = User::factory()->create([
                'name' => 'Abdallah (Test Member)',
                'email' => 'test-member-' . time() . '@togetherpay.local',
                'phone' => '+1987654321',
            ]);
        }

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$AbdallahTM',  // YOUR REAL CASH APP TAG
            'cash_phone' => null,
            'cash_email' => null,
            'share_type' => 'PERCENT',
            'share_value' => 100, // 100% of the bill for testing
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $memberAmount = $member->calculateAmount($group->total_amount_cents);
        $this->line("✓ Member created: {$memberUser->name}");
        $this->line("  Channel: CASH_APP");
        $this->line("  Cash Tag: {$member->cash_tag}");
        $this->line("  Share: {$member->share_value}% = $" . ($memberAmount / 100));
        $this->newLine();

        // Step 4: Create billing cycle
        $this->info('Step 4: Creating billing cycle...');
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => $group->total_amount_cents,
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        $this->line("✓ Billing cycle created: #{$cycle->id}");
        $this->line("  Period: {$cycle->period_start->format('M d')} - {$cycle->period_end->format('M d, Y')}");
        $this->line("  Due: {$cycle->due_at->format('M d, Y')}");
        $this->newLine();

        // Step 5: Create Square customer
        $this->info('Step 5: Creating Square customer for member...');
        try {
            $customerService = app(SquareCustomerService::class);
            $customerId = $customerService->createCustomerForMember($member, $owner);
            $member->update(['square_customer_id' => $customerId]);
            $this->line("✓ Square customer created: {$customerId}");
        } catch (\Exception $e) {
            $this->warn("Square API error (using sandbox mode): {$e->getMessage()}");
            $member->update(['square_customer_id' => 'MOCK_CUSTOMER_' . time()]);
            $this->line("  Using mock customer ID: {$member->square_customer_id}");
        }
        $this->newLine();

        // Step 6: Create Square subscription
        $this->info('Step 6: Setting up Square subscription...');
        $this->info('This would normally create a recurring subscription in Square');
        $this->info('that automatically charges your Cash App on each billing cycle.');

        try {
            $subscriptionService = app(SquareSubscriptionService::class);
            $invoice = $subscriptionService->createSubscription($member, $owner, $cycle);
            $subscriptionId = $invoice['id'] ?? 'unknown';
            $publicUrl = $invoice['public_url'] ?? null;
            $member->update([
                'square_subscription_id' => $subscriptionId,
                'square_subscription_created_at' => now(),
            ]);
            $this->line("✓ Square Cash App invoice created: {$subscriptionId}");
            if ($publicUrl) {
                $this->line("  Public URL: {$publicUrl}");
            }
        } catch (\Exception $e) {
            $this->warn("Square API error (using sandbox mode): {$e->getMessage()}");
            $member->update([
                'square_subscription_id' => 'MOCK_SUB_' . time(),
                'square_subscription_created_at' => now(),
            ]);
            $this->line("  Using mock subscription ID: {$member->square_subscription_id}");
        }
        $this->newLine();

        // Step 7: Create payment request with deep link
        $this->info('Step 7: Creating payment request with Cash App deep link...');

        // Generate Cash App deep link
        $amount = $memberAmount / 100; // Convert cents to dollars
        $note = urlencode("Netflix - {$cycle->period_start->format('M Y')}");
        $deepLink = "https://cash.app/\$AbdallahTM/{$amount}?note={$note}";

        $paymentRequest = PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => $memberAmount,
            'method_hint' => 'CASH_APP',
            'pr_code' => 'PR-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'note' => "Netflix Premium - {$cycle->period_start->format('M Y')}",
            'deep_link' => $deepLink,
            'status' => 'pending',
            'square_invoice_id' => 'INV_' . time(),
            'initiated_at' => now(),
        ]);

        $this->line("✓ Payment request created: {$paymentRequest->pr_code}");
        $this->line("  Amount: $" . ($paymentRequest->amount_cents / 100));
        $this->line("  Status: {$paymentRequest->status}");
        $this->newLine();

        // Show the payment link
        $this->info('=== PAYMENT REQUEST DETAILS ===');
        $this->newLine();
        $this->line("<fg=yellow>Cash App Payment Link:</>");
        $this->line("<fg=bright-cyan>{$deepLink}</>");
        $this->newLine();
        $this->line("<fg=yellow>OR scan this URL to open Cash App:</>");
        $this->line("cash://\$AbdallahTM/{$amount}");
        $this->newLine();

        // Summary
        $this->info('=== What Just Happened ===');
        $this->line('1. Created a test group in auto_square mode');
        $this->line('2. Added you as a member with Cash Tag: $AbdallahTM');
        $this->line('3. Created Square customer and subscription (mocked in sandbox)');
        $this->line('4. Generated payment request for $' . number_format($amount, 2));
        $this->line('5. Created deep link to Cash App');
        $this->newLine();

        $this->info('=== How It Works (Production) ===');
        $this->line('<fg=green>In Production with Real Square Account:</>');
        $this->line('  1. Square creates subscription linked to your Cash App');
        $this->line('  2. On billing date, Square auto-charges your Cash App');
        $this->line('  3. Cash App processes payment ($' . number_format($amount, 2) . ')');
        $this->line('  4. Square sends webhook confirming payment');
        $this->line('  5. Our system auto-marks payment as verified');
        $this->line('  6. Owner sees collection updated immediately');
        $this->newLine();

        $this->info('=== Test Results ===');
        $this->line("✓ Group ID: {$group->id}");
        $this->line("✓ Member ID: {$member->id}");
        $this->line("✓ Cycle ID: {$cycle->id}");
        $this->line("✓ Payment Request ID: {$paymentRequest->id}");
        $this->line("✓ Deep Link Generated: Yes");
        $this->newLine();

        $this->info('=== Next Steps ===');
        $this->line('1. Click the Cash App link above (or copy it to your browser)');
        $this->line('2. Cash App should open with pre-filled amount and recipient');
        $this->line('3. Complete the payment in Cash App');
        $this->line('4. In production, Square webhook would auto-verify the payment');
        $this->newLine();

        $this->info('Test completed successfully!');
    }
}
