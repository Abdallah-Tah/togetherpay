<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Services\Square\SquareInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generate Square invoices for all members in an auto_square billing cycle
 *
 * This job is triggered by Laravel Scheduler before the cycle due date.
 * It creates Square invoices with Cash App Pay enabled for each active member.
 */
class GenerateSquareInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private BillingCycle $cycle
    ) {
    }

    public function handle(SquareInvoiceService $invoiceService): void
    {
        $group = $this->cycle->group;

        // Only process auto_square groups
        if (!$group->isAutoSquareMode()) {
            Log::info('Skipping non-auto-square group', [
                'group_id' => $group->id,
                'billing_mode' => $group->billing_mode,
            ]);
            return;
        }

        // Owner must have Square connected
        if (!$group->ownerHasSquare()) {
            Log::warning('Auto-square group has no Square account connected', [
                'group_id' => $group->id,
                'owner_id' => $group->owner_id,
            ]);
            return;
        }

        $owner = $group->owner;
        $activeMembers = $group->activeMembers()->get();

        Log::info('Generating Square invoices for billing cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
            'member_count' => $activeMembers->count(),
        ]);

        foreach ($activeMembers as $member) {
            try {
                // Skip if payment request already exists
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

                // Ensure member has Square customer ID
                if (!$member->square_customer_id) {
                    Log::warning('Member missing Square customer ID', [
                        'member_id' => $member->id,
                    ]);
                    continue;
                }

                // Create Square invoice
                $invoiceId = $invoiceService->createInvoiceForMember($member, $owner, $this->cycle);

                // Calculate member's share
                $amountCents = $member->calculateAmount($group->total_amount_cents);

                // Create payment request record
                $paymentRequest = PaymentRequest::create([
                    'group_id' => $group->id,
                    'cycle_id' => $this->cycle->id,
                    'member_id' => $member->id,
                    'amount_cents' => $amountCents,
                    'method_hint' => $member->preferred_channel,
                    'pr_code' => 'PR-' . strtoupper(Str::random(8)),
                    'note' => "{$group->name} - {$this->cycle->period_start->format('M Y')}",
                    'status' => 'sent',
                    'square_invoice_id' => $invoiceId,
                    'initiated_at' => now(),
                    'sent_at' => now(),
                ]);

                Log::info('Square invoice created successfully', [
                    'payment_request_id' => $paymentRequest->id,
                    'invoice_id' => $invoiceId,
                    'member_id' => $member->id,
                    'amount' => $amountCents / 100,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create Square invoice for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Continue with other members
                continue;
            }
        }

        Log::info('Completed Square invoice generation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
        ]);
    }
}
