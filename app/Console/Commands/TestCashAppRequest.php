<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Services\CashAppService;
use App\Jobs\SendCashAppPaymentRequestJob;
use Illuminate\Console\Command;

class TestCashAppRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cashapp-request
                            {--owner-email= : Email of the group owner}
                            {--member-cashtag= : Cash App tag of the member (e.g., $IkramBarkad)}
                            {--amount=5.00 : Amount to request in dollars}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending a Cash App payment request';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Cash App Payment Request...');
        $this->newLine();

        // Get or create owner
        $ownerEmail = $this->option('owner-email') ?? $this->ask('Owner email address');
        $owner = User::where('email', $ownerEmail)->first();

        if (!$owner) {
            $this->error("❌ Owner with email {$ownerEmail} not found!");
            return Command::FAILURE;
        }

        $this->info("✅ Owner: {$owner->name} ({$owner->email})");
        $this->info("   Cash App: {$owner->cashapp_tag}");

        // Get member cashtag
        $memberCashtag = $this->option('member-cashtag') ?? $this->ask('Member Cash App tag (e.g., $IkramBarkad)');

        // Find or create member user
        $member = User::where('cashapp_tag', $memberCashtag)->first();

        if (!$member) {
            $this->warn("⚠️  Member with cashtag {$memberCashtag} not found. Creating test member...");
            $member = User::create([
                'name' => str_replace('$', '', $memberCashtag),
                'email' => strtolower(str_replace('$', '', $memberCashtag)) . '@test.com',
                'password' => bcrypt('password'),
                'cashapp_tag' => $memberCashtag,
                'cashapp_customer_id' => 'test_customer_' . uniqid(), // In production, this comes from OAuth
            ]);
            $this->info("✅ Created test member: {$member->name}");
        }

        $this->info("✅ Member: {$member->name} ({$member->email})");
        $this->info("   Cash App: {$member->cashapp_tag}");
        $this->info("   Customer ID: {$member->cashapp_customer_id}");
        $this->newLine();

        // Get amount
        $amount = (float) ($this->option('amount') ?? 5.00);
        $amountCents = (int) ($amount * 100);

        // Get or create test group
        $group = Group::where('owner_id', $owner->id)->first();

        if (!$group) {
            $this->warn("⚠️  No group found for owner. Creating test group...");
            $group = Group::create([
                'owner_id' => $owner->id,
                'name' => 'Test Group - ' . now()->format('M d, Y'),
                'total_amount' => $amount * 100, // in cents
                'currency' => 'USD',
                'billing_cadence' => 'MONTHLY',
                'due_day_of_month' => now()->day,
                'grace_period_days' => 3,
                'timezone' => 'America/New_York',
                'status' => 'ACTIVE',
            ]);
            $this->info("✅ Created test group: {$group->name}");
        }

        // Add member to group if not already
        $groupMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', $member->id)
            ->first();

        if (!$groupMember) {
            $groupMember = GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $member->id,
                'preferred_channel' => 'CASH_APP',
                'cash_tag' => $member->cashapp_tag,
                'share_type' => 'EQUAL',
                'share_value' => $amount,
                'status' => 'ACTIVE',
            ]);
            $this->info("✅ Added member to group");
        }

        // Create billing cycle
        $cycle = BillingCycle::where('group_id', $group->id)
            ->where('status', 'PENDING')
            ->first();

        if (!$cycle) {
            $cycle = BillingCycle::create([
                'group_id' => $group->id,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'due_date' => now()->addDays(1),
                'status' => 'PENDING',
            ]);
            $this->info("✅ Created billing cycle");
        }

        // Create payment request
        $paymentRequest = PaymentRequest::create([
            'cycle_id' => $cycle->id,
            'member_id' => $groupMember->id,
            'amount_cents' => $amountCents,
            'currency' => 'USD',
            'status' => 'PENDING',
            'payment_method' => 'CASH_APP',
            'due_at' => now()->addDays(1),
        ]);

        $this->info("✅ Created payment request: PR #{$paymentRequest->id}");
        $this->info("   Amount: \${$amount}");
        $this->info("   Status: {$paymentRequest->status}");
        $this->newLine();

        // Test Cash App API call
        $this->info('🚀 Sending Cash App payment request...');
        $this->newLine();

        try {
            $cashAppService = app(CashAppService::class);

            $result = $cashAppService->createPaymentRequest([
                'amount' => $amountCents,
                'currency' => 'USD',
                'scope_id' => $member->cashapp_customer_id,
                'reference_id' => "PR_{$paymentRequest->id}",
                'requester_name' => $owner->name,
                'redirect_url' => route('payment.callback', ['payment_request' => $paymentRequest->id]),
                'idempotency_key' => "TEST_PR_{$paymentRequest->id}_" . time(),
            ]);

            if ($result) {
                $this->info('✅ SUCCESS! Cash App request created!');
                $this->newLine();
                $this->info('📱 Cash App Request Details:');
                $this->info("   Request ID: {$result['id']}");
                $this->info("   Status: {$result['status']}");

                if (isset($result['auth_flow_triggers']['mobile_url'])) {
                    $this->info("   Mobile URL: {$result['auth_flow_triggers']['mobile_url']}");
                }

                if (isset($result['auth_flow_triggers']['desktop_url'])) {
                    $this->info("   Desktop URL: {$result['auth_flow_triggers']['desktop_url']}");
                }

                // Update payment request with Cash App data
                $paymentRequest->update([
                    'metadata_json' => [
                        'cashapp_request_id' => $result['id'],
                        'cashapp_status' => $result['status'],
                        'cashapp_mobile_url' => $result['auth_flow_triggers']['mobile_url'] ?? null,
                        'cashapp_desktop_url' => $result['auth_flow_triggers']['desktop_url'] ?? null,
                    ]
                ]);

                $this->newLine();
                $this->info('✅ Payment request updated with Cash App details');
            } else {
                $this->error('❌ Failed to create Cash App request');
                $this->warn('Check logs for details');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->warn('Stack trace:');
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('🎉 Test completed!');

        return Command::SUCCESS;
    }
}
