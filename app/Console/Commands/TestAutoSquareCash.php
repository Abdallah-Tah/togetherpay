<?php

namespace App\Console\Commands;

use App\Jobs\CreateAutoSquareInvoices;
use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestAutoSquareCash extends Command
{
    protected $signature = 'test:auto-square-cash {--force : Skip confirmation}';

    protected $description = 'Test Square auto-cash collection with real connected account';

    public function handle(): void
    {
        $this->info('=== Testing Square Auto-Cash Collection ===');
        $this->info('This will create a real test using your connected Square account');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Continue with test?')) {
                $this->info('Cancelled.');
                return;
            }
        }

        // Find user with connected Square account
        $owner = User::whereNotNull('square_access_token')
            ->whereNotNull('square_merchant_id')
            ->first();

        if (!$owner) {
            $this->error('No user with connected Square account found!');
            $this->line('Please connect your Square account first in settings.');
            return;
        }

        $this->line("✓ Found owner with Square account: {$owner->name} ({$owner->email})");
        $this->line("  Merchant ID: {$owner->square_merchant_id}");
        $this->line("  Location ID: {$owner->square_location_id}");
        $this->newLine();

        // Step 1: Create test group
        $this->info('Step 1: Creating test group with auto_square billing...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Auto-Square Test - ' . now()->format('H:i:s'),
            'description' => 'Real test of Square auto-cash collection',
            'total_amount_cents' => 1000, // $10.00
            'billing_mode' => 'auto_square',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group created: {$group->name}");
        $this->line("  Group ID: {$group->id}");
        $this->line("  Billing Mode: auto_square");
        $this->newLine();

        // Step 2: Add test members (you as a member)
        $this->info('Step 2: Adding test members...');

        // Create test members
        $testMembers = [
            [
                'name' => 'Test Member 1',
                'email' => 'test1-' . time() . '@togetherpay.local',
                'phone' => '+12065551001',
                'cash_tag' => '$TestMember1',
                'share' => 500, // $5.00
            ],
            [
                'name' => 'Test Member 2',
                'email' => 'test2-' . time() . '@togetherpay.local',
                'phone' => '+12065551002',
                'cash_tag' => '$TestMember2',
                'share' => 500, // $5.00
            ],
        ];

        $memberIds = [];
        foreach ($testMembers as $testData) {
            $user = User::firstOrCreate(
                ['email' => $testData['email']],
                [
                    'name' => $testData['name'],
                    'phone' => $testData['phone'],
                    'password' => bcrypt('password'),
                ]
            );

            $member = GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'preferred_channel' => 'CASH_APP',
                'cash_tag' => $testData['cash_tag'],
                'cash_phone' => $testData['phone'],
                'cash_email' => $testData['email'],
                'share_type' => 'AMOUNT',
                'share_value' => $testData['share'] / 100,
                'status' => 'active',
                'verified_at' => now(),
            ]);

            $memberIds[] = $member->id;
            $shareAmount = $testData['share'] / 100;
            $this->line("  ✓ {$testData['name']}: {$testData['email']} (Share: \${$shareAmount})");
        }

        $this->newLine();

        // Step 3: Create billing cycle
        $this->info('Step 3: Creating billing cycle...');
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => 1000, // $10.00
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        $this->line("✓ Billing cycle created");
        $this->line("  Cycle ID: {$cycle->id}");
        $this->line("  Total Amount: \$10.00");
        $this->line("  Due Date: {$cycle->due_at->format('M d, Y')}");
        $this->newLine();

        // Step 4: Dispatch job to create Square invoices
        $this->info('Step 4: Creating Square invoices with Cash App Pay...');
        $this->line('Dispatching CreateAutoSquareInvoices...');

        try {
            CreateAutoSquareInvoices::dispatch($cycle);

            $this->line("✓ Job dispatched successfully!");
            $this->newLine();

            $this->info('=== What Happens Next ===');
            $this->line('The background job will:');
            $this->line('  1. Create Square customers for each test member');
            $this->line('  2. Create orders for each member\'s share amount');
            $this->line('  3. Create and publish Square invoices with Cash App Pay enabled');
            $this->line('  4. Members receive invoice notifications with a Cash App payment option');
            $this->newLine();

            $this->info('=== Monitor Progress ===');
            $this->line('Watch the logs in real-time with:');
            $this->line('  <fg=cyan>php artisan pail</>');
            $this->newLine();

            $this->info('=== Verify in Square Dashboard ===');
            $this->line('Log into your <fg=cyan>Square Sandbox Dashboard</>: https://sandbox.squareup.com');
            $this->line('  1. Go to <fg=yellow>Customers</> - should see 2 new customers');
            $this->line('  2. Go to <fg=yellow>Invoices</> - should see 2 invoices ($5 each)');
            $this->line('  3. Each invoice has a public payment link with Cash App Pay option');
            $this->newLine();

            $this->info('=== Test Group Details ===');
            $this->line("Group ID: {$group->id}");
            $this->line("Group Name: {$group->name}");
            $this->line("Owner: {$owner->name}");
            $this->line("Merchant ID: {$owner->square_merchant_id}");
            $this->newLine();

            $this->info('✓ Test created successfully!');
        } catch (\Exception $e) {
            $this->error("Failed to dispatch job: {$e->getMessage()}");
            $this->line($e->getTraceAsString());
        }
    }
}
