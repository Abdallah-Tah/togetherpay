<?php

namespace Database\Seeders;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create current user
        $owner = User::first();

        if (!$owner) {
            $owner = User::factory()->create([
                'name' => 'Demo Owner',
                'email' => 'owner@example.com',
                'role' => 'owner',
            ]);
        } else {
            $owner->update(['role' => 'owner']);
        }

        // Create additional users for members
        $member1 = User::firstOrCreate(
            ['email' => 'john@example.com'],
            ['name' => 'John Smith', 'password' => bcrypt('password')]
        );

        $member2 = User::firstOrCreate(
            ['email' => 'jane@example.com'],
            ['name' => 'Jane Doe', 'password' => bcrypt('password')]
        );

        $member3 = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob Johnson', 'password' => bcrypt('password')]
        );

        // Create Group 1: Netflix Subscription
        $group1 = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Netflix Premium',
            'total_amount_cents' => 2299, // $22.99
            'cadence' => 'MONTHLY',
            'due_day' => 1,
            'grace_days' => 3,
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Add members to Netflix group
        GroupMember::create([
            'group_id' => $group1->id,
            'user_id' => $member1->id,
            'share_type' => 'AMOUNT',
            'share_value' => 5.75,
            'preferred_channel' => 'VENMO',
            'venmo_username' => 'john-smith',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        GroupMember::create([
            'group_id' => $group1->id,
            'user_id' => $member2->id,
            'share_type' => 'AMOUNT',
            'share_value' => 5.75,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => 'janedoe',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        GroupMember::create([
            'group_id' => $group1->id,
            'user_id' => $member3->id,
            'share_type' => 'AMOUNT',
            'share_value' => 5.75,
            'preferred_channel' => 'VENMO',
            'venmo_username' => 'bob-j',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Create Group 2: Spotify Family
        $group2 = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Spotify Family',
            'total_amount_cents' => 1999, // $19.99
            'cadence' => 'MONTHLY',
            'due_day' => 15,
            'grace_days' => 5,
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Add members to Spotify group
        GroupMember::create([
            'group_id' => $group2->id,
            'user_id' => $member1->id,
            'share_type' => 'PERCENT',
            'share_value' => 33.33,
            'preferred_channel' => 'VENMO',
            'venmo_username' => 'john-smith',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        GroupMember::create([
            'group_id' => $group2->id,
            'user_id' => $member2->id,
            'share_type' => 'PERCENT',
            'share_value' => 33.33,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => 'janedoe',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Create billing cycle for Netflix (current month)
        $cycle1 = BillingCycle::create([
            'group_id' => $group1->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->startOfMonth()->addDay(),
            'status' => 'collecting',
        ]);

        // Create payment requests for this cycle
        $paymentRequest1 = PaymentRequest::create([
            'cycle_id' => $cycle1->id,
            'member_id' => $group1->members->first()->id,
            'amount_cents' => 575,
            'channel' => 'VENMO',
            'link' => 'venmo://paycharge?txn=pay&recipients=john-smith&amount=5.75&note=Netflix+Premium+-+' . now()->format('M+Y'),
            'status' => 'paid',
            'sent_at' => now()->subDays(10),
            'paid_at' => now()->subDays(8),
        ]);

        $paymentRequest2 = PaymentRequest::create([
            'cycle_id' => $cycle1->id,
            'member_id' => $group1->members->get(1)->id,
            'amount_cents' => 575,
            'channel' => 'CASH_APP',
            'link' => 'https://cash.app/$janedoe/5.75',
            'status' => 'sent',
            'sent_at' => now()->subDays(10),
        ]);

        $paymentRequest3 = PaymentRequest::create([
            'cycle_id' => $cycle1->id,
            'member_id' => $group1->members->get(2)->id,
            'amount_cents' => 575,
            'channel' => 'VENMO',
            'link' => 'venmo://paycharge?txn=pay&recipients=bob-j&amount=5.75&note=Netflix+Premium+-+' . now()->format('M+Y'),
            'status' => 'pending',
        ]);

        // Create billing cycle for Spotify (previous month - closed)
        $cycle2 = BillingCycle::create([
            'group_id' => $group2->id,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'due_at' => now()->subMonth()->startOfMonth()->addDays(15),
            'status' => 'closed',
        ]);

        // Create payment requests for Spotify (all paid)
        PaymentRequest::create([
            'cycle_id' => $cycle2->id,
            'member_id' => $group2->members->first()->id,
            'amount_cents' => 666,
            'channel' => 'VENMO',
            'status' => 'paid',
            'sent_at' => now()->subMonth()->addDays(10),
            'paid_at' => now()->subMonth()->addDays(12),
        ]);

        PaymentRequest::create([
            'cycle_id' => $cycle2->id,
            'member_id' => $group2->members->get(1)->id,
            'amount_cents' => 666,
            'channel' => 'CASH_APP',
            'status' => 'paid',
            'sent_at' => now()->subMonth()->addDays(10),
            'paid_at' => now()->subMonth()->addDays(13),
        ]);

        $this->command->info('✅ Demo data created successfully!');
        $this->command->info('📧 Login with: ' . $owner->email);
        $this->command->info('🔑 Password: password');
    }
}
