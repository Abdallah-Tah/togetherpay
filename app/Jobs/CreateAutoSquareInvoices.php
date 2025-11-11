<?php

namespace App\Jobs;

use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use App\Services\Square\SquareCustomerService;
use App\Services\Square\SquareInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Creates Square invoices for each member in an auto_square billing cycle
 * Dispatched when a billing cycle is created for auto_square groups
 */
class CreateAutoSquareInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BillingCycle $cycle)
    {
    }

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
        if (!$owner->hasSquareAccount()) {
            Log::error('Owner missing Square connection', [
                'owner_id' => $owner->id,
                'group_id' => $group->id,
            ]);
            return;
        }

        $customerService = new SquareCustomerService();
        $invoiceService = new SquareInvoiceService();

        Log::info('Starting invoice generation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
            'group_name' => $group->name,
            'total_members' => $group->members()->count(),
        ]);

        foreach ($group->members()->where('status', 'active')->get() as $member) {
            try {
                // Step 1: Ensure member has Square customer ID
                if (!$member->square_customer_id) {
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

                // Step 2: Create and publish invoice
                Log::info('Creating invoice for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                ]);

                $invoiceId = $invoiceService->createInvoiceForMember($member, $owner, $this->cycle);

                // Step 3: Create payment request record linked to invoice
                $amountCents = $member->calculateAmount($this->cycle->total_amount_cents);

                $paymentRequest = PaymentRequest::create([
                    'group_id' => $group->id,
                    'cycle_id' => $this->cycle->id,
                    'member_id' => $member->id,
                    'amount_cents' => $amountCents,
                    'method_hint' => 'SQUARE_INVOICE',
                    'status' => 'sent',
                    'sent_at' => now(),
                    'square_invoice_id' => $invoiceId,
                ]);

                Log::info('Created payment request for invoice', [
                    'payment_request_id' => $paymentRequest->id,
                    'invoice_id' => $invoiceId,
                    'member_id' => $member->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create invoice for member', [
                    'member_id' => $member->id,
                    'cycle_id' => $this->cycle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Finished invoice generation for cycle', [
            'cycle_id' => $this->cycle->id,
            'group_id' => $group->id,
        ]);
    }
}
