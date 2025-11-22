<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareSubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generates Cash App Pay invoices for each member in an auto_square billing cycle.
 * Keeps legacy naming for backwards compatibility inside the queue table.
 */
class CreateAutoSquareInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BillingCycle $cycle) {}

    public function handle(): void
    {
        $group = $this->cycle->group;

        // Only process auto_square groups
        if ($group->billing_mode !== 'auto_square') {
            Log::info('Skipping non-auto_square group', ['group_id' => $group->id]);

            return;
        }

        $owner = $group->owner;

        // Verify owner has Square connection
        if (! $owner->hasSquareAccount()) {
            Log::error('Owner missing Square connection', [
                'owner_id' => $owner->id,
                'group_id' => $group->id,
            ]);

            return;
        }

        $customerService = new SquareCustomerService;
        $subscriptionService = app(SquareSubscriptionService::class);

        Log::info('Starting Cash App invoice generation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
            'group_name' => $group->name,
            'total_members' => $group->members()->count(),
        ]);

        foreach ($group->members()->where('status', 'active')->get() as $member) {
            try {
                $existingRequest = PaymentRequest::where('cycle_id', $this->cycle->id)
                    ->where('member_id', $member->id)
                    ->first();

                if ($existingRequest) {
                    Log::info('Payment request already exists for member', [
                        'member_id' => $member->id,
                        'payment_request_id' => $existingRequest->id,
                    ]);

                    continue;
                }

                // Step 1: Ensure member has Square customer ID
                if (! $member->square_customer_id) {
                    Log::info('Creating Square customer for member', [
                        'member_id' => $member->id,
                        'group_id' => $group->id,
                    ]);

                    $customerId = $customerService->createCustomerForMember($member, $owner);
                    $member->update(['square_customer_id' => $customerId]);

                    Log::info('Created Square customer for member', [
                        'member_id' => $member->id,
                        'customer_id' => $customerId,
                    ]);
                }

                // Step 2: Create Cash App Pay invoice (via subscription service wrapper)
                Log::info('Creating Cash App invoice for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                ]);

                $invoice = $subscriptionService->createSubscription($member, $owner, $this->cycle);
                $invoiceId = $invoice['id'] ?? null;
                $invoiceUrl = $invoice['public_url'] ?? null;

                // Step 3: Create payment request record linked to invoice
                $amountCents = $member->calculateAmount($this->cycle->total_amount_cents);

                $paymentRequest = PaymentRequest::create([
                    'group_id' => $group->id,
                    'cycle_id' => $this->cycle->id,
                    'member_id' => $member->id,
                    'amount_cents' => $amountCents,
                    'method_hint' => 'CASH_APP',
                    'pr_code' => 'PR-'.strtoupper(Str::random(8)),
                    'note' => "{$group->name} - {$this->cycle->period_start->format('M Y')}",
                    'status' => 'sent',
                    'square_invoice_id' => $invoiceId,
                    'metadata' => [
                        'square_public_url' => $invoiceUrl,
                    ],
                    'initiated_at' => now(),
                    'sent_at' => now(),
                ]);

                $member->update([
                    'square_subscription_id' => $invoiceId,
                    'square_subscription_created_at' => now(),
                ]);

                Log::info('Created payment request for Cash App invoice', [
                    'payment_request_id' => $paymentRequest->id,
                    'invoice_id' => $invoiceId,
                    'member_id' => $member->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create Cash App invoice for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Finished Cash App invoice generation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
        ]);
    }
}
