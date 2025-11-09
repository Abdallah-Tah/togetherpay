<?php

namespace Tests\Feature\API;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentRequestsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_group_payment_requests(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $memberUser = User::factory()->create(['role' => 'member']);

        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Test Group',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'cadence' => 'MONTHLY',
            'due_day' => 1,
            'grace_days' => 5,
            'total_amount_cents' => 10000,
            'status' => 'active',
        ]);

        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->endOfMonth(),
            'status' => 'collecting',
            'total_amount_cents' => 10000,
            'collected_amount_cents' => 0,
        ]);

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'status' => 'active',
            'preferred_channel' => 'VENMO',
            'share_type' => 'AMOUNT',
            'share_value' => 100,
        ]);

        PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000,
            'status' => 'pending',
            'method_hint' => 'VENMO',
            'pr_code' => Str::uuid()->toString(),
            'deep_link' => 'https://example.com',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/payment-requests');

        $response->assertOk()
            ->assertJsonPath('data.0.group_id', $group->id);
    }
}
