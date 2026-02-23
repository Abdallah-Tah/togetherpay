<?php

namespace Database\Seeders;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScreenshotDemoSeeder extends Seeder
{
    /**
     * Seed demo data for App Store screenshots
     * Run with: php artisan db:seed --class=ScreenshotDemoSeeder
     */
    public function run(): void
    {
        // Find or create demo owner account
        $owner = User::firstOrCreate(
            ['email' => 'demo@togetherpay.app'],
            [
                'name' => 'Demo Account',
                'password' => bcrypt('DemoPassword123!'),
                'email_verified_at' => now(),
                'onboarding_completed_at' => now(),
                'venmo_username' => '@demo-togetherpay',
                'cash_tag' => '$demotogetherpay',
            ]
        );

        $this->command->info("Demo account: demo@togetherpay.app / DemoPassword123!");

        // Clean up existing demo groups for this user
        Group::where('owner_id', $owner->id)->delete();

        // Group 1: Family Phone Plan
        $familyPhone = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Family Phone Plan',
            'total_amount_cents' => 18000, // $180
            'due_day' => 15,
            'cadence' => 'monthly',
            'billing_mode' => 'manual',
            'status' => 'active',
        ]);

        $familyPhoneCycle = BillingCycle::create([
            'group_id' => $familyPhone->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->setDay(15),
            'status' => 'open',
        ]);

        $this->createMember($familyPhone, $familyPhoneCycle, 'Sarah Johnson', 'sarah.johnson@example.com', '@sarah-johnson', null, 4500, 'PAID');
        $this->createMember($familyPhone, $familyPhoneCycle, 'Mike Chen', 'mike.chen@example.com', null, '$mikechen', 4500, 'PAID');
        $this->createMember($familyPhone, $familyPhoneCycle, 'Emily Davis', 'emily.davis@example.com', '@emilydavis', null, 4500, 'PENDING');
        $this->createMember($familyPhone, $familyPhoneCycle, 'James Wilson', 'james.wilson@example.com', null, '$jameswilson', 4500, 'PENDING');

        // Group 2: Netflix Premium
        $netflix = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Netflix Premium',
            'total_amount_cents' => 2299, // $22.99
            'due_day' => 1,
            'cadence' => 'monthly',
            'billing_mode' => 'manual',
            'status' => 'active',
        ]);

        $netflixCycle = BillingCycle::create([
            'group_id' => $netflix->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->setDay(1),
            'status' => 'open',
        ]);

        $this->createMember($netflix, $netflixCycle, 'Alex Martinez', 'alex.martinez@example.com', '@alexmartinez', null, 575, 'PAID');
        $this->createMember($netflix, $netflixCycle, 'Rachel Green', 'rachel.green@example.com', null, '$rachelgreen', 575, 'PAID');
        $this->createMember($netflix, $netflixCycle, 'Tom Anderson', 'tom.anderson@example.com', '@tom-anderson', null, 575, 'PENDING');
        $this->createMember($netflix, $netflixCycle, 'Lisa Brown', 'lisa.brown@example.com', null, '$lisabrown', 574, 'PENDING');

        // Group 3: Apartment Utilities
        $utilities = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Apartment Utilities',
            'total_amount_cents' => 24000, // $240
            'due_day' => 5,
            'cadence' => 'monthly',
            'billing_mode' => 'manual',
            'status' => 'active',
        ]);

        $utilitiesCycle = BillingCycle::create([
            'group_id' => $utilities->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->setDay(5),
            'status' => 'open',
        ]);

        $this->createMember($utilities, $utilitiesCycle, 'David Park', 'david.park@example.com', '@davidpark', null, 8000, 'PAID');
        $this->createMember($utilities, $utilitiesCycle, 'Jessica Lee', 'jessica.lee@example.com', null, '$jessicalee', 8000, 'PENDING');
        $this->createMember($utilities, $utilitiesCycle, 'Ryan Taylor', 'ryan.taylor@example.com', '@ryantaylor', null, 8000, 'PENDING');

        // Group 4: Spotify Family
        $spotify = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Spotify Family',
            'total_amount_cents' => 1999, // $19.99
            'due_day' => 20,
            'cadence' => 'monthly',
            'billing_mode' => 'manual',
            'status' => 'active',
        ]);

        $spotifyCycle = BillingCycle::create([
            'group_id' => $spotify->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->setDay(20),
            'status' => 'open',
        ]);

        $this->createMember($spotify, $spotifyCycle, 'Chris Moore', 'chris.moore@example.com', null, '$chrismoore', 400, 'PAID');
        $this->createMember($spotify, $spotifyCycle, 'Amanda White', 'amanda.white@example.com', '@amanda-white', null, 400, 'PAID');
        $this->createMember($spotify, $spotifyCycle, 'Kevin Garcia', 'kevin.garcia@example.com', null, '$kevingarcia', 400, 'PAID');
        $this->createMember($spotify, $spotifyCycle, 'Nicole Thomas', 'nicole.thomas@example.com', '@nicolethomas', null, 399, 'PENDING');
        $this->createMember($spotify, $spotifyCycle, 'Brandon Hill', 'brandon.hill@example.com', null, '$brandonhill', 400, 'PENDING');

        $this->command->info('✅ Created 4 demo groups with members and payment requests');
        $this->command->info('📧 Login with: demo@togetherpay.app / DemoPassword123!');
        $this->command->warn('⚠️  Remember to delete this demo data after screenshots!');
        $this->command->warn('    Run: php artisan db:seed --class=CleanupScreenshotDemoSeeder');
    }

    /**
     * Create a group member with a payment request
     */
    private function createMember(
        Group $group,
        BillingCycle $cycle,
        string $name,
        string $email,
        ?string $venmoUsername,
        ?string $cashTag,
        int $amountCents,
        string $status
    ): void {
        // Calculate share_value as dollars
        $shareValue = $amountCents / 100;

        $member = GroupMember::create([
            'group_id' => $group->id,
            'name' => $name,
            'email' => $email,
            'phone' => null,
            'share_type' => 'AMOUNT',
            'share_value' => $shareValue,
            'venmo_username' => $venmoUsername,
            'cash_tag' => $cashTag,
            'preferred_channel' => $venmoUsername ? 'venmo' : 'cash_app',
            'status' => 'active',
        ]);

        // Create a payment request for this member
        PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => $amountCents,
            'status' => $status,
            'sent_at' => now()->subDays(3),
            'paid_at' => $status === 'PAID' ? now()->subDays(1) : null,
            'confirmed_at' => $status === 'PAID' ? now()->subDays(1) : null,
            'metadata' => [
                'note' => "Payment for {$group->name}",
            ],
        ]);

        $this->command->info("  → Created member: {$name} ({$status})");
    }
}
