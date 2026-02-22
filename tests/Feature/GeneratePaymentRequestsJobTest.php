<?php

use App\Jobs\GeneratePaymentRequestsJob;
use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates payment requests for draft cycle', function () {
    // Create owner with payment channels configured
    $owner = User::factory()->create([
        'venmo_username' => '@ownervenmo',
        'cashapp_tag' => '$ownercash',
        'role' => 'owner',
    ]);

    // Create group
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'billing_mode' => 'manual',
        'cadence' => 'MONTHLY',
        'due_day' => 1,
        'grace_days' => 5,
        'total_amount_cents' => 10000,
        'status' => 'active',
    ]);

    // Create member with preferred channel
    $member = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Test Member',
        'email' => 'member@test.com',
        'preferred_channel' => 'VENMO',
        'venmo_username' => '@membervenmo',
        'cash_tag' => '$membercash',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    // Create draft cycle
    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => now()->endOfMonth(),
        'status' => 'draft',
        'total_amount_cents' => 10000,
        'collected_amount_cents' => 0,
    ]);

    // Run the job
    $job = new GeneratePaymentRequestsJob;
    $job->handle(app(\App\Services\PaymentRequestService::class));

    // Assert cycle status changed to collecting
    expect($cycle->fresh()->status)->toBe('collecting');

    // Assert payment request was created
    $paymentRequest = $cycle->paymentRequests()->first();
    expect($paymentRequest)->not->toBeNull()
        ->and($paymentRequest->member_id)->toBe($member->id)
        ->and($paymentRequest->amount_cents)->toBe(10000);

    // Assert metadata contains payment_options
    $metadata = $paymentRequest->metadata;
    expect($metadata)->toHaveKey('payment_options')
        ->and($metadata['payment_options'])->toBeArray()
        ->and(count($metadata['payment_options']))->toBeGreaterThan(0);

    // Assert preferred channel is marked
    $preferredOption = collect($metadata['payment_options'])
        ->firstWhere('is_preferred', true);

    expect($preferredOption)->not->toBeNull()
        ->and($preferredOption['channel'])->toBe('VENMO');

    // Assert Venmo link contains owner's handle
    expect($preferredOption['uri'])->toContain('ownervenmo');
});

test('generates requests for members with different channel preferences', function () {
    $owner = User::factory()->create([
        'venmo_username' => '@ownervenmo',
        'cashapp_tag' => '$ownercash',
        'role' => 'owner',
    ]);

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'billing_mode' => 'manual',
        'cadence' => 'MONTHLY',
        'due_day' => 1,
        'grace_days' => 5,
        'total_amount_cents' => 20000,
        'status' => 'active',
    ]);

    // Member prefers Venmo
    $venmoMember = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Venmo Member',
        'email' => 'venmo@test.com',
        'preferred_channel' => 'VENMO',
        'venmo_username' => '@venmouser',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    // Member prefers Cash App
    $cashMember = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Cash Member',
        'email' => 'cash@test.com',
        'preferred_channel' => 'CASH_APP',
        'cash_tag' => '$cashuser',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => now()->endOfMonth(),
        'status' => 'draft',
        'total_amount_cents' => 20000,
        'collected_amount_cents' => 0,
    ]);

    // Run the job
    $job = new GeneratePaymentRequestsJob;
    $job->handle(app(\App\Services\PaymentRequestService::class));

    // Assert 2 payment requests were created
    expect($cycle->paymentRequests()->count())->toBe(2);

    // Check Venmo member's request
    $venmoRequest = $cycle->paymentRequests()
        ->where('member_id', $venmoMember->id)
        ->first();

    $venmoPreferred = collect($venmoRequest->metadata['payment_options'])
        ->firstWhere('is_preferred', true);

    expect($venmoPreferred['channel'])->toBe('VENMO')
        ->and($venmoPreferred['uri'])->toContain('ownervenmo');

    // Check Cash App member's request
    $cashRequest = $cycle->paymentRequests()
        ->where('member_id', $cashMember->id)
        ->first();

    $cashPreferred = collect($cashRequest->metadata['payment_options'])
        ->firstWhere('is_preferred', true);

    expect($cashPreferred['channel'])->toBe('CASH_APP')
        ->and($cashPreferred['uri'])->toContain('ownercash');
});

test('only includes owner supported channels', function () {
    // Owner only has Venmo configured
    $owner = User::factory()->create([
        'venmo_username' => '@ownervenmo',
        'cashapp_tag' => null, // No Cash App
        'role' => 'owner',
    ]);

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'billing_mode' => 'manual',
        'cadence' => 'MONTHLY',
        'due_day' => 1,
        'grace_days' => 5,
        'total_amount_cents' => 10000,
        'status' => 'active',
    ]);

    // Member prefers Cash App but owner doesn't support it
    $member = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Test Member',
        'email' => 'member@test.com',
        'preferred_channel' => 'CASH_APP',
        'cash_tag' => '$membercash',
        'venmo_username' => '@membervenmo',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => now()->endOfMonth(),
        'status' => 'draft',
        'total_amount_cents' => 10000,
        'collected_amount_cents' => 0,
    ]);

    // Run the job
    $job = new GeneratePaymentRequestsJob;
    $job->handle(app(\App\Services\PaymentRequestService::class));

    $paymentRequest = $cycle->paymentRequests()->first();
    $paymentOptions = $paymentRequest->metadata['payment_options'];

    // Should only have Venmo option (owner doesn't support Cash App)
    expect(count($paymentOptions))->toBe(1)
        ->and($paymentOptions[0]['channel'])->toBe('VENMO');
});

test('skips members without verified contact info', function () {
    $owner = User::factory()->create([
        'venmo_username' => '@ownervenmo',
        'role' => 'owner',
    ]);

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'billing_mode' => 'manual',
        'cadence' => 'MONTHLY',
        'due_day' => 1,
        'grace_days' => 5,
        'total_amount_cents' => 10000,
        'status' => 'active',
    ]);

    // Member without email or phone (not verified)
    $unverifiedMember = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Unverified Member',
        'email' => null,
        'phone' => null,
        'user_id' => null,
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    // Verified member
    $verifiedMember = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Verified Member',
        'email' => 'verified@test.com',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => now()->endOfMonth(),
        'status' => 'draft',
        'total_amount_cents' => 10000,
        'collected_amount_cents' => 0,
    ]);

    // Run the job
    $job = new GeneratePaymentRequestsJob;
    $job->handle(app(\App\Services\PaymentRequestService::class));

    // Should only create request for verified member
    expect($cycle->paymentRequests()->count())->toBe(1)
        ->and($cycle->paymentRequests()->first()->member_id)->toBe($verifiedMember->id);
});

test('payment options are sorted with preferred channel first', function () {
    $owner = User::factory()->create([
        'venmo_username' => '@ownervenmo',
        'cashapp_tag' => '$ownercash',
        'role' => 'owner',
    ]);

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'timezone' => 'UTC',
        'currency' => 'USD',
        'billing_mode' => 'manual',
        'cadence' => 'MONTHLY',
        'due_day' => 1,
        'grace_days' => 5,
        'total_amount_cents' => 10000,
        'status' => 'active',
    ]);

    $member = GroupMember::create([
        'group_id' => $group->id,
        'name' => 'Test Member',
        'email' => 'member@test.com',
        'preferred_channel' => 'CASH_APP', // Prefers Cash App
        'venmo_username' => '@membervenmo',
        'cash_tag' => '$membercash',
        'share_type' => 'AMOUNT',
        'share_value' => 100,
        'status' => 'active',
    ]);

    $cycle = BillingCycle::create([
        'group_id' => $group->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'due_at' => now()->endOfMonth(),
        'status' => 'draft',
        'total_amount_cents' => 10000,
        'collected_amount_cents' => 0,
    ]);

    // Run the job
    $job = new GeneratePaymentRequestsJob;
    $job->handle(app(\App\Services\PaymentRequestService::class));

    $paymentRequest = $cycle->paymentRequests()->first();
    $paymentOptions = $paymentRequest->metadata['payment_options'];

    // First option should be the preferred channel (Cash App)
    expect($paymentOptions[0]['channel'])->toBe('CASH_APP')
        ->and($paymentOptions[0]['is_preferred'])->toBeTrue();

    // Second option should not be preferred
    if (count($paymentOptions) > 1) {
        expect($paymentOptions[1]['is_preferred'])->toBeFalse();
    }
});
