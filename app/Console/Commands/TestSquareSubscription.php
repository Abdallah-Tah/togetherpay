<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\Square\SquareSubscriptionService;
use App\Services\Square\SquareCustomerService;
use Illuminate\Console\Command;

class TestSquareSubscription extends Command
{
    protected $signature = 'test:square-subscription {member_email} {member_name?} {member_phone?} {--force : Skip confirmation} {--owner_id= : Use specific owner ID instead of first connected}';

    protected $description = 'Create a REAL Square subscription for recurring monthly billing with Cash App Pay';

    public function handle(): void
    {
        $memberEmail = $this->argument('member_email');
        $memberName = $this->argument('member_name') ?: 'Test Member';
        $memberPhone = $this->argument('member_phone') ?: '+12065551000';
        $ownerId = $this->option('owner_id');

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  Creating REAL Square Subscription with Monthly Cash App Pay   ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Find owner with connected Square account
        if ($ownerId) {
            $owner = User::find($ownerId);
            if (!$owner) {
                $this->error("Owner with ID {$ownerId} not found!");
                return;
            }
        } else {
            $owner = User::whereNotNull('square_access_token')
                ->whereNotNull('square_merchant_id')
                ->first();

            if (!$owner) {
                $this->error('❌ No user with connected Square account found!');
                $this->line('Please connect your Square account first in settings.');
                $this->newLine();
                $this->info('Steps to connect:');
                $this->line('  1. Go to your account settings');
                $this->line('  2. Click "Connect Square Account"');
                $this->line('  3. Authorize the connection');
                return;
            }
        }

        $this->line("✓ Found owner with Square connection");
        $this->line("  Name: {$owner->name}");
        $this->line("  Email: {$owner->email}");
        $this->line("  Merchant ID: {$owner->square_merchant_id}");
        $this->line("  Location ID: {$owner->square_location_id}");
        $this->newLine();

        // Verify location ID is set
        if (!$owner->square_location_id) {
            $this->error('❌ Owner is missing Square location ID!');
            $this->line('This usually happens during OAuth connection.');
            $this->line('Try reconnecting your Square account.');
            return;
        }

        if (!$this->option('force')) {
            $this->newLine();
            $this->line("<fg=yellow>Member Details:</>");
            $this->line("  Email: {$memberEmail}");
            $this->line("  Name: {$memberName}");
            $this->line("  Phone: {$memberPhone}");
            $this->line("  Amount: \$5.00/month");
            $this->newLine();
            if (!$this->confirm('Create subscription and set up recurring billing?')) {
                $this->info('Cancelled.');
                return;
            }
        }

        $this->newLine();

        // Step 1: Create group
        $this->info('Step 1: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Square Subscription Test - ' . now()->format('H:i:s'),
            'description' => 'Testing Square Subscription + Cash App Pay integration',
            'total_amount_cents' => 500, // $5.00
            'billing_mode' => 'auto_square',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group created: {$group->name}");
        $this->newLine();

        // Step 2: Create/find member
        $this->info('Step 2: Setting up member...');
        $memberUser = User::firstOrCreate(
            ['email' => $memberEmail],
            [
                'name' => $memberName,
                'phone' => $memberPhone,
                'password' => bcrypt('password'),
            ]
        );

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_phone' => $memberPhone,
            'cash_email' => $memberEmail,
            'share_type' => 'AMOUNT',
            'share_value' => 5.00,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->line("✓ Member created");
        $this->line("  Name: {$memberName}");
        $this->line("  Email: {$memberEmail}");
        $this->line("  Phone: {$memberPhone}");
        $this->newLine();

        // Step 3: Create billing cycle
        $this->info('Step 3: Creating billing cycle...');
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

        $this->line("✓ Billing cycle created");
        $this->line("  Cycle ID: {$cycle->id}");
        $this->line("  Due Date: {$cycle->due_at->format('M d, Y')}");
        $this->newLine();

        // Step 4: Create Square customer
        $this->info('Step 4: Creating Square customer for member...');
        try {
            $owner->refresh();
            $customerService = app(SquareCustomerService::class);
            $customerId = $customerService->createCustomerForMember($member, $owner);
            $member->update(['square_customer_id' => $customerId]);

            $this->line("✓ Square customer created");
            $this->line("  Customer ID: {$customerId}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to create Square customer: {$e->getMessage()}");
            return;
        }
        $this->newLine();

        // Step 5: Create subscription with monthly recurring billing
        $this->info('Step 5: Creating Square Subscription with Monthly Cash App Pay...');
        $this->line("<fg=yellow>This will:</>");
        $this->line("  • Create a recurring subscription for: {$memberEmail}");
        $this->line("  • Amount: \$5.00/month");
        $this->line("  • Payment method: Cash App Pay");
        $this->line("  • Square sends monthly Cash App notification to member");
        $this->newLine();

        try {
            $owner->refresh();
            $subscriptionService = app(SquareSubscriptionService::class);
            $subscriptionId = $subscriptionService->createSubscription($member, $owner, $cycle);

            $this->line("✓ Subscription created!");
            $this->line("  Subscription ID: {$subscriptionId}");
            $this->newLine();

            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║                    ✓ SUCCESS!                                  ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');
            $this->newLine();

            $this->line("<fg=green>💳 Recurring Monthly Payment:</>");
            $this->line("  Each month on the same date:");
            $this->line("  1. Square automatically charges the subscription");
            $this->line("  2. Member receives Cash App notification");
            $this->line("  3. Member confirms payment in Cash App");
            $this->line("  4. Money flows directly to your Square account");
            $this->newLine();

            $this->line("<fg=green>✓ Webhook Confirmation:</>");
            $this->line("  When payment is received:");
            $this->line("  • Square sends webhook: <fg=cyan>subscription.charge.succeeded</>");
            $this->line("  • Our app auto-marks payment as <fg=cyan>auto_verified</>");
            $this->line("  • Notification sent to group owner");
            $this->newLine();

            $this->line("<fg=green>✓ Owner Benefits:</>");
            $this->line("  • Money deposited directly to your bank account");
            $this->line("  • No money flows through TogetherPay platform");
            $this->line("  • You remain the merchant (owner as merchant model)");
            $this->line("  • App only orchestrates and tracks payments");
            $this->newLine();

            $this->line("<fg=yellow>📊 Test Data:</>");
            $this->line("  Owner: {$owner->name} ({$owner->email})");
            $this->line("  Member: {$memberName} ({$memberEmail})");
            $this->line("  Group ID: {$group->id}");
            $this->line("  Subscription ID: {$subscriptionId}");
            $this->line("  Amount: \$5.00/month");
            $this->newLine();

            $this->info('Next Steps:');
            $this->line("  1. Member will receive Cash App notification from Square");
            $this->line("  2. Member confirms the monthly payment in Cash App");
            $this->line("  3. Watch the webhook confirmation in real-time logs:");
            $this->line("  <fg=cyan>php artisan pail</>");
            $this->line("  4. Payment will be marked as auto_verified");
            $this->newLine();

            $this->line("<fg=cyan>Monthly Billing Schedule:</>");
            $this->line("  • Starting: {$cycle->due_at->format('M d, Y')}");
            $this->line("  • Repeating: 1st of each month");
            $this->line("  • Amount: \$5.00");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('❌ Failed to create subscription!');
            $this->newLine();
            $this->line("<fg=red>Error:</> {$e->getMessage()}");
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
