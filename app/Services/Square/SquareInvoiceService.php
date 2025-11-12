<?php

namespace App\Services\Square;

use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Manages Square Invoices for monthly billing
 * Creates orders, invoices, and publishes them for member payment
 */
class SquareInvoiceService
{
    private SquareOrderService $orderService;
    private SquareApiClient $client;

    /**
     * Create an invoice for a group member
     */
    public function createInvoiceForMember(GroupMember $member, User $owner, BillingCycle $cycle): array
    {
        if (!$member->square_customer_id) {
            throw new \Exception("Member does not have a Square customer ID");
        }

        if (!$owner->square_location_id) {
            throw new \Exception("Owner does not have a Square location ID");
        }

        $this->client = new SquareApiClient($owner);
        $this->orderService = new SquareOrderService();

        // Calculate the member's share
        $amountCents = $member->calculateAmount($cycle->total_amount_cents);
        $amountDollars = $amountCents / 100;

        // Step 1: Create an order
        $orderId = $this->orderService->createOrder(
            $owner,
            $amountCents,
            "Group contribution for cycle " . $cycle->id
        );

        // Step 2: Create the invoice with the order
        $invoiceData = [
            'idempotency_key' => 'invoice_' . Str::random(12),
            'invoice' => [
                'location_id' => $owner->square_location_id,
                'order_id' => $orderId,
                'primary_recipient' => [
                    'customer_id' => $member->square_customer_id,
                ],
                'payment_requests' => [
                    [
                        'request_type' => 'BALANCE',
                        'due_date' => $cycle->due_at->toDateString(),
                    ],
                ],
                // Enable automatic sending (Square will handle email/SMS)
                'delivery_method' => 'EMAIL',
                'description' => "Group contribution for billing cycle: {$cycle->id}",
                'accepted_payment_methods' => [
                    'card' => true,
                    'cash_app_pay' => true,
                ],
            ],
        ];

        $invoice = $this->client->createInvoice($invoiceData);

        // Step 3: Publish the invoice
        $publishedInvoice = $this->client->publishInvoice($invoice['id'], $invoice['version']);

        \Illuminate\Support\Facades\Log::info('Created and published invoice for member', [
            'member_id' => $member->id,
            'invoice_id' => $publishedInvoice['id'],
            'amount' => $amountDollars,
            'public_url' => $publishedInvoice['public_url'] ?? null,
        ]);

        return $publishedInvoice;
    }

    /**
     * Get invoice details from Square
     */
    public function getInvoice(string $invoiceId, User $owner): array
    {
        $this->client = new SquareApiClient($owner);
        return $this->client->getInvoice($invoiceId);
    }
}
