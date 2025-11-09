<?php

namespace Tests\Feature\API;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_fetch_summary(): void
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
            'preferred_channel' => 'VENMO',
            'share_type' => 'AMOUNT',
            'share_value' => 100,
            'status' => 'active',
        ]);

        PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 5000,
            'method_hint' => 'VENMO',
            'status' => 'paid',
            'sent_at' => now(),
            'paid_at' => now(),
        ]);

        PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 3000,
            'method_hint' => 'VENMO',
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        PaymentRequest::create([
            'group_id' => $group->id,
            'cycle_id' => $cycle->id,
            'member_id' => $member->id,
            'amount_cents' => 2000,
            'method_hint' => 'VENMO',
            'status' => 'failed',
            'sent_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $query = http_build_query([
            'group_id' => $group->id,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/reports/summary?'.$query);

        $response->assertOk()
            ->assertJsonPath('group.id', $group->id)
            ->assertJsonPath('stats.total_requests', 3)
            ->assertJsonPath('stats.paid_requests', 1)
            ->assertJsonPath('stats.pending_requests', 1)
            ->assertJsonPath('stats.failed_requests', 1)
            ->assertJsonPath('stats.total_collected', 50)
            ->assertJsonPath('stats.total_pending', 30);
    }
}
