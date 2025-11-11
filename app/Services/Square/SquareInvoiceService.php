<?php

declare(strict_types=1);

namespace App\Services\Square;

use App\Models\BillingCycle;
use App\Models\GroupMember;
use App\Models\User;
use Square\Invoices\Requests\CancelInvoiceRequest;
use Square\Invoices\Requests\CreateInvoiceRequest;
use Square\Invoices\Requests\GetInvoicesRequest;
use Square\Invoices\Requests\PublishInvoiceRequest;
use Square\Types\Invoice;
use Square\Types\InvoiceAcceptedPaymentMethods;
use Square\Types\InvoicePaymentRequest;
use Square\Types\InvoiceRecipient;
use Square\Types\Money;

/**
 * Manages Square Invoice creation for recurring billing
 *
 * This service creates Square invoices with Cash App Pay enabled for group members.
 * Uses Square SDK v43.2+ with constructor-based object creation.
 */
final class SquareInvoiceService
{
    /**
     * Create a Square invoice for a group member with Cash App Pay enabled
     *
     * Creates a draft invoice, then immediately publishes it to send to the customer.
     * The invoice enables both card and Cash App Pay payment methods.
     *
     * @param  GroupMember  $member  The group member to invoice
     * @param  BillingCycle  $cycle  The billing cycle for this invoice
     * @return string The Square invoice ID
     *
     * @throws \Exception If member lacks Square customer ID or invoice creation fails
     */
    public function createInvoiceForMember(GroupMember $member, BillingCycle $cycle): string
    {
        if (! $member->square_customer_id) {
            throw new \Exception('Member does not have a Square customer ID');
        }

        $group = $cycle->group;
        $owner = $group->owner;
        $client = SquareClientBuilder::buildForUser($owner);
        $invoicesApi = $client->invoices;

        // Calculate member's share in cents
        $amountCents = $member->calculateAmount($group->total_amount_cents);

        // Build invoice recipient (customer)
        $recipient = new InvoiceRecipient([
            'customerId' => $member->square_customer_id,
        ]);

        // Build money object for the payment amount
        $money = new Money([
            'amount' => $amountCents,
            'currency' => 'USD',
        ]);

        // Build payment request with due date
        $paymentRequest = new InvoicePaymentRequest([
            'requestType' => 'BALANCE',
            'dueDate' => $cycle->due_at->format('Y-m-d'),
            'fixedAmountRequestedMoney' => $money,
        ]);

        // Enable Cash App Pay and card payments
        $acceptedMethods = new InvoiceAcceptedPaymentMethods([
            'card' => true,
            'cashAppPay' => true,
        ]);

        // Build the invoice
        $invoice = new Invoice([
            'locationId' => $owner->square_location_id,
            'primaryRecipient' => $recipient,
            'paymentRequests' => [$paymentRequest],
            'acceptedPaymentMethods' => $acceptedMethods,
            'deliveryMethod' => 'EMAIL',
            'title' => $group->name,
            'description' => "Payment for {$cycle->period_start->format('M Y')}",
        ]);

        try {
            // Create the invoice (draft)
            $createRequest = new CreateInvoiceRequest([
                'invoice' => $invoice,
                'idempotencyKey' => 'invoice_'.time().'_'.$member->id.'_'.uniqid(),
            ]);

            $createResponse = $invoicesApi->create($createRequest);
            $createdInvoice = $createResponse->getInvoice();

            if (! $createdInvoice) {
                throw new \Exception('Invoice creation returned no invoice object');
            }

            $invoiceId = $createdInvoice->getId();
            $invoiceVersion = $createdInvoice->getVersion();

            logger()->info('Square invoice created', [
                'invoice_id' => $invoiceId,
                'member_id' => $member->id,
                'amount_cents' => $amountCents,
            ]);

            // Publish the invoice (sends notification to customer)
            $publishRequest = new PublishInvoiceRequest([
                'invoiceId' => $invoiceId,
                'version' => $invoiceVersion,
                'idempotencyKey' => 'publish_'.time().'_'.$member->id.'_'.uniqid(),
            ]);

            $invoicesApi->publish($publishRequest);

            logger()->info('Square invoice published', [
                'invoice_id' => $invoiceId,
                'member_id' => $member->id,
            ]);

            return $invoiceId;
        } catch (\Exception $e) {
            logger()->error('Failed to create Square invoice', [
                'member_id' => $member->id,
                'customer_id' => $member->square_customer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a Square invoice
     *
     * @param  string  $invoiceId  The Square invoice ID to cancel
     * @param  User  $owner  The group owner who owns this invoice
     *
     * @throws \Exception If invoice retrieval or cancellation fails
     */
    public function cancelInvoice(string $invoiceId, User $owner): void
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $invoicesApi = $client->invoices;

        try {
            // Get current invoice version
            $getRequest = new GetInvoicesRequest([
                'invoiceId' => $invoiceId,
            ]);
            $response = $invoicesApi->get($getRequest);
            $invoice = $response->getInvoice();

            if (! $invoice) {
                throw new \Exception('Invoice not found');
            }

            // Cancel the invoice
            $cancelRequest = new CancelInvoiceRequest([
                'invoiceId' => $invoiceId,
                'version' => $invoice->getVersion(),
            ]);
            $invoicesApi->cancel($cancelRequest);

            logger()->info('Square invoice cancelled', [
                'invoice_id' => $invoiceId,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to cancel Square invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get invoice details from Square
     *
     * @param  string  $invoiceId  The Square invoice ID to retrieve
     * @param  User  $owner  The group owner who owns this invoice
     * @return array<string, mixed> Invoice details
     *
     * @throws \Exception If invoice retrieval fails
     */
    public function getInvoice(string $invoiceId, User $owner): array
    {
        $client = SquareClientBuilder::buildForUser($owner);
        $invoicesApi = $client->invoices;

        try {
            $getRequest = new GetInvoicesRequest([
                'invoiceId' => $invoiceId,
            ]);
            $response = $invoicesApi->get($getRequest);
            $invoice = $response->getInvoice();

            if (! $invoice) {
                throw new \Exception('Invoice not found');
            }

            return [
                'id' => $invoice->getId(),
                'status' => $invoice->getStatus(),
                'public_url' => $invoice->getPublicUrl(),
                'title' => $invoice->getTitle(),
                'description' => $invoice->getDescription(),
                'created_at' => $invoice->getCreatedAt(),
                'updated_at' => $invoice->getUpdatedAt(),
            ];
        } catch (\Exception $e) {
            logger()->error('Failed to retrieve Square invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
