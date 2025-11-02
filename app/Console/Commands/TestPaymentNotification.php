<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\PaymentRequestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class TestPaymentNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payment-notification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test payment notification email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Creating test data...');

        // Create a test user (or use existing)
        $testUser = User::where('email', $email)->first();

        if (!$testUser) {
            $testUser = User::create([
                'name' => 'Test User',
                'email' => $email,
                'password' => bcrypt('password'),
            ]);
            $this->info("Created test user: {$email}");
        } else {
            $this->info("Using existing user: {$email}");
        }

        // Create a test group owner (or use existing)
        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Group Owner',
                'password' => bcrypt('password'),
            ]
        );

        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Test Group - Family Phone Plan',
            'cadence' => 'MONTHLY',
            'due_day' => 15,
            'grace_days' => 7,
            'total_amount_cents' => 15000, // $150.00
            'status' => 'active',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ]);

        $this->info("Created test group: {$group->name}");

        // Create a test member
        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $testUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$TestUser',
            'share_type' => 'AMOUNT',
            'share_value' => 5000, // $50.00
            'status' => 'active',
        ]);

        $this->info("Created test member with share: $50.00");

        // Create a test billing cycle
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(15),
            'status' => 'draft',
        ]);

        $this->info("Created test billing cycle");

        // Create a test payment request
        $paymentRequest = PaymentRequest::create([
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000, // $50.00
            'status' => 'pending',
            'method_hint' => 'CASH_APP',
            'sent_at' => now(),
        ]);

        $this->info("Created test payment request: $50.00");

        // Send the notification
        $this->info("Sending payment notification to {$email}...");

        try {
            $testUser->notify(new PaymentRequestNotification($paymentRequest));
            $this->info('✅ Email notification sent successfully!');
            $this->newLine();
            $this->info('Check your inbox at: ' . $email);
            $this->info('Subject: 💰 Payment Due: Test Group - Family Phone Plan');
            $this->newLine();
            $this->info('Payment link: ' . url("/pay/{$paymentRequest->id}"));
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            $this->newLine();
            $this->error('Check your .env mail configuration:');
            $this->error('  MAIL_MAILER=' . config('mail.default'));
            $this->error('  MAIL_HOST=' . config('mail.mailers.smtp.host'));
            $this->error('  MAIL_PORT=' . config('mail.mailers.smtp.port'));
            $this->error('  MAIL_USERNAME=' . config('mail.mailers.smtp.username'));
            $this->error('  MAIL_ENCRYPTION=' . config('mail.mailers.smtp.encryption'));
        }

        // Cleanup test data
        $this->newLine();
        if ($this->confirm('Delete test data?', true)) {
            $paymentRequest->delete();
            $cycle->delete();
            $member->delete();
            $group->delete();
            $owner->delete();

            if ($testUser->email !== $email || $this->confirm('Delete test user?', false)) {
                $testUser->delete();
            }

            $this->info('✅ Test data cleaned up');
        }

        return 0;
    }
}
