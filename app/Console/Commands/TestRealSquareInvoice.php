<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\Square\SquareInvoiceService;
use App\Services\Square\SquareCustomerService;
use Illuminate\Console\Command;

class TestRealSquareInvoice extends Command
{
    protected $signature = 'test:real-square-invoice {member_email} {member_name?} {member_phone?} {amount=5} {--force : Skip confirmation} {--owner_id= : Use specific owner ID instead of first connected}';

    protected $description = 'Create a REAL Square invoice sent to member email with Cash App Pay option';

    public function handle(): void
    {
        $memberEmail = $this->argument('member_email');
        $memberName = $this->argument('member_name') ?: 'Test Member';
        $memberPhone = $this->argument('member_phone') ?: '+12065551000';
        $ownerId = $this->option('owner_id');
        $amountDollars = (float) $this->argument('amount');
        $amountCents = (int) round($amountDollars * 100);

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║     Creating REAL Square Invoice with Cash App Pay             ║');
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
            $this->line('  Amount: $' . number_format($amountDollars, 2));
            $this->newLine();
            if (!$this->confirm('Create invoice and send to member?')) {
                $this->info('Cancelled.');
                return;
            }
        }

        $this->newLine();

        // Step 1: Create group
        $this->info('Step 1: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Square Invoice Test - ' . now()->format('H:i:s'),
            'description' => 'Testing Square Invoice + Cash App Pay integration',
            'total_amount_cents' => $amountCents,
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
            'share_value' => $amountDollars,
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
            'total_amount_cents' => $amountCents,
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

        // Step 5: Create Square invoice with Cash App Pay
        $this->info('Step 5: Creating Square Invoice with Cash App Pay...');
        $this->line("<fg=yellow>This will:</>");
        $this->line("  • Send invoice to: {$memberEmail}");
        $this->line('  • Amount: $' . number_format($amountDollars, 2));
        $this->line("  • Payment method: Cash App Pay");
        $this->newLine();

        try {
            $owner->refresh();
            $invoiceService = app(SquareInvoiceService::class);
            $invoice = $invoiceService->createInvoiceForMember($member, $owner, $cycle);
            $invoiceId = $invoice['id'] ?? 'unknown';
            $invoiceUrl = $invoice['public_url'] ?? null;

            $this->line("✓ Invoice created and published!");
            $this->line("  Invoice ID: {$invoiceId}");
            if ($invoiceUrl) {
                $this->line("  Public URL: {$invoiceUrl}");
            }
            $this->newLine();

            $this->info('╔════════════════════════════════════════════════════════════════╗');
            $this->info('║                    ✓ SUCCESS!                                  ║');
            $this->info('╚════════════════════════════════════════════════════════════════╝');
            $this->newLine();

            $this->line("<fg=green>📧 Email Notification:</>");
            $this->line("  {$memberName} will receive an email at:");
            $this->line("  <fg=cyan>{$memberEmail}</>");
            $this->line("  with a Square invoice and Cash App Pay button");
            $this->newLine();

            $this->line("<fg=green>💳 Cash App Payment:</>");
            $this->line("  1. Member receives email with payment link");
            $this->line("  2. Clicks the link or taps \"Pay with Cash App\"");
            $this->line('  3. Cash App opens with $' . number_format($amountDollars, 2) . " payment request");
            $this->line("  4. Swipes to confirm payment");
            $this->line("  5. Payment completes instantly");
            $this->newLine();

            $this->line("<fg=green>✓ Webhook Confirmation:</>");
            $this->line("  When payment is received:");
            $this->line("  • Square sends webhook: <fg=cyan>invoice.payment_made</>");
            $this->line("  • Our app auto-marks payment as <fg=cyan>auto_verified</>");
            $this->line("  • Notification sent to group owner");
            $this->newLine();

            $this->line("<fg=yellow>📊 Test Data:</>");
            $this->line("  Owner: {$owner->name} ({$owner->email})");
            $this->line("  Member: {$memberName} ({$memberEmail})");
            $this->line("  Group ID: {$group->id}");
            $this->line("  Invoice ID: {$invoiceId}");
            $this->line('  Amount: $' . number_format($amountDollars, 2));
            $this->newLine();

            $this->info('Next Steps:');
            $this->line("  1. Check {$memberEmail} for Square invoice email");
            $this->line("  2. Click the payment link");
            $this->line("  3. Choose \"Pay with Cash App\"");
            $this->line("  4. Complete payment in Cash App");
            $this->line("  5. Watch the webhook confirmation in logs");
            $this->newLine();

            $this->line("<fg=cyan>Monitor real-time logs with:</> php artisan pail");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('❌ Failed to create Square invoice!');
            $this->newLine();
            $this->line("<fg=red>Error:</> {$e->getMessage()}");
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
