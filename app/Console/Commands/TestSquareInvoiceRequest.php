<?php

namespace App\Console\Commands;

use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Square\SquareClient;
use Square\Environments;
use Square\Models\Money;
use Square\Models\InvoicePaymentRequest as SquareInvoicePaymentRequest;
use Square\Models\CreateInvoiceRequest;
use Square\Models\Invoice;

class TestSquareInvoiceRequest extends Command
{
    protected $signature = 'test:square-invoice-request';

    protected $description = 'Test real Square invoice payment request from owner to member via Cash App';

    public function handle(): void
    {
        $this->info('=== Real Square Invoice + Cash App Pay Test ===');
        $this->info('Owner: $IkramBarkad → Member: $AbdallahTM for $5.00');
        $this->newLine();

        // Step 1: Create/get owner with Cash App
        $this->info('Step 1: Setting up owner with Cash App ($IkramBarkad)...');

        $owner = User::factory()->create([
            'name' => 'Ikram Barkad',
            'email' => 'ikram-owner-' . time() . '@togetherpay.local',
            'phone' => '+12065551234',
        ]);

        // Connect owner to Square with real access token
        $squareAccessToken = config('square.access_token');
        $squareLocationId = config('square.location_id');

        if (empty($squareAccessToken)) {
            $this->error('Square access token not configured in .env');
            $this->line('Please set SQUARE_ACCESS_TOKEN in your .env file');
            return;
        }

        $owner->update([
            'square_merchant_id' => 'MERCHANT_' . time(),
            'square_access_token' => encrypt($squareAccessToken),
            'square_location_id' => $squareLocationId ?: 'MAIN',
            'square_connected_at' => now(),
        ]);

        $this->line("✓ Owner created: {$owner->name}");
        $this->line("  Email: {$owner->email}");
        $this->line("  Square Connected: Yes");
        $this->newLine();

        // Step 2: Create group
        $this->info('Step 2: Creating test group...');
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Test Payment Group',
            'description' => 'Testing Square invoice payment request',
            'total_amount_cents' => 500, // $5.00
            'billing_mode' => 'auto_square',
            'cadence' => 'monthly',
            'status' => 'active',
        ]);

        $this->line("✓ Group: {$group->name}");
        $this->line("  Total: $" . ($group->total_amount_cents / 100));
        $this->newLine();

        // Step 3: Create member (YOU) with Cash App
        $this->info('Step 3: Adding member with Cash App ($AbdallahTM)...');

        $memberUser = User::factory()->create([
            'name' => 'Abdallah Mohamed',
            'email' => 'abdallah-member-' . time() . '@togetherpay.local',
            'phone' => '+12065559999',
        ]);

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $memberUser->id,
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$AbdallahTM',
            'cash_phone' => '+12065559999',
            'cash_email' => 'abdallah@test.com',
            'share_type' => 'AMOUNT',
            'share_value' => 5.00,
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->line("✓ Member: {$memberUser->name}");
        $this->line("  Cash App: {$member->cash_tag}");
        $this->line("  Amount: $5.00");
        $this->newLine();

        // Step 4: Create billing cycle
        $this->info('Step 4: Creating billing cycle...');
        $cycle = BillingCycle::create([
            'group_id' => $group->id,
            'sequence_number' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_at' => now()->addDays(7),
            'total_amount_cents' => $group->total_amount_cents,
            'total_collected_cents' => 0,
            'status' => 'active',
        ]);

        $this->line("✓ Cycle #{$cycle->id}");
        $this->newLine();

        // Step 5: Create Square customer for member
        $this->info('Step 5: Creating Square customer for member...');

        try {
            $squareClient = new SquareClient(
                token: $squareAccessToken,
                options: ['environment' => config('square.environment') === 'production'
                    ? Environments::Production->value
                    : Environments::Sandbox->value]
            );

            $customersApi = $squareClient->customers;

            // Create customer
            $response = $customersApi->create(
                givenName: 'Abdallah',
                familyName: 'Mohamed',
                emailAddress: 'abdallah@test.com',
                phoneNumber: '+12065559999',
                idempotencyKey: 'customer_' . time() . '_' . $member->id
            );
            $customer = $response->getResult()->getCustomer();
            $customerId = $customer->getId();

            $member->update(['square_customer_id' => $customerId]);

            $this->line("✓ Square customer created: {$customerId}");
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("Failed to create Square customer: {$e->getMessage()}");
            $this->line("Make sure your Square credentials are correct in .env");
            return;
        }

        // Step 6: Create Square Invoice with Cash App Pay
        $this->info('Step 6: Creating Square invoice payment request...');
        $this->line('This will send a payment request that you can pay with Cash App');
        $this->newLine();

        try {
            $invoicesApi = $squareClient->invoices;

            // Create invoice
            $invoice = new Invoice();
            $invoice->setLocationId($owner->square_location_id ?: config('square.location_id'));

            // Set order details
            $invoice->setOrderId(null);

            // Set customer
            $invoice->setPrimaryRecipient(new \Square\Models\InvoiceRecipient());
            $invoice->getPrimaryRecipient()->setCustomerId($customerId);

            // Create payment request (the $5 charge)
            $paymentRequest = new SquareInvoicePaymentRequest();
            $paymentRequest->setRequestType('BALANCE');
            $paymentRequest->setDueDate(now()->addDays(7)->format('Y-m-d'));

            // Set amount ($5.00)
            $requestMoney = new Money();
            $requestMoney->setAmount(500); // $5.00 in cents
            $requestMoney->setCurrency('USD');
            $paymentRequest->setComputedAmountMoney($requestMoney);

            $invoice->setPaymentRequests([$paymentRequest]);

            // Set accepted payment methods (Cash App Pay)
            $invoice->setAcceptedPaymentMethods(new \Square\Models\InvoiceAcceptedPaymentMethods());
            $invoice->getAcceptedPaymentMethods()->setCard(true);
            $invoice->getAcceptedPaymentMethods()->setCashAppPay(true);

            // Set title and description
            $invoice->setTitle('Test Payment Request');
            $invoice->setDescription('Payment request from Test Payment Group');

            // Create the invoice
            $createRequest = new CreateInvoiceRequest($invoice);
            $createRequest->setIdempotencyKey('invoice_' . time() . '_' . $member->id);

            $createResponse = $invoicesApi->createInvoice($createRequest);
            $createdInvoice = $createResponse->getResult()->getInvoice();
            $invoiceId = $createdInvoice->getId();

            $this->line("✓ Invoice created: {$invoiceId}");

            // Publish the invoice (this makes it active and sends notification)
            $publishRequest = new \Square\Models\PublishInvoiceRequest($createdInvoice->getVersion());
            $publishRequest->setIdempotencyKey('publish_' . time() . '_' . $member->id);

            $publishResponse = $invoicesApi->publishInvoice($invoiceId, $publishRequest);
            $publishedInvoice = $publishResponse->getResult()->getInvoice();

            $this->line("✓ Invoice published and active");

            // Get the payment URL
            $paymentUrl = $publishedInvoice->getPublicUrl();

            $this->newLine();
            $this->info('=== PAYMENT REQUEST CREATED ===');
            $this->newLine();

            $this->line("<fg=green>Invoice ID:</> {$invoiceId}");
            $this->line("<fg=green>Amount:</> \$5.00");
            $this->line("<fg=green>From:</> Owner (\$IkramBarkad)");
            $this->line("<fg=green>To:</> Member (\$AbdallahTM)");
            $this->newLine();

            if ($paymentUrl) {
                $this->line("<fg=yellow>Payment URL:</>");
                $this->line("<fg=bright-cyan>{$paymentUrl}</>");
                $this->newLine();
                $this->line("👆 Open this URL to see the payment request");
                $this->line("   You can pay with Cash App Pay");
            }

            // Save to database
            $paymentRequest = PaymentRequest::create([
                'group_id' => $group->id,
                'cycle_id' => $cycle->id,
                'member_id' => $member->id,
                'amount_cents' => 500,
                'method_hint' => 'CASH_APP',
                'pr_code' => 'PR-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'note' => 'Test Payment Request',
                'deep_link' => $paymentUrl,
                'status' => 'sent',
                'square_invoice_id' => $invoiceId,
                'initiated_at' => now(),
                'sent_at' => now(),
            ]);

            $this->newLine();
            $this->info('✓ Payment request saved to database');
            $this->line("  PR Code: {$paymentRequest->pr_code}");
            $this->line("  PR ID: {$paymentRequest->id}");
            $this->newLine();

            $this->info('=== What Happens Next ===');
            $this->line('1. Open the payment URL above in your browser');
            $this->line('2. Square shows invoice for $5.00');
            $this->line('3. Click "Pay with Cash App Pay"');
            $this->line('4. Cash App opens with payment details');
            $this->line('5. Complete payment in Cash App');
            $this->line('6. Square sends webhook to confirm payment');
            $this->line('7. Payment status updates to "auto_verified"');
            $this->newLine();

            $this->info('Test completed successfully!');

        } catch (\Exception $e) {
            $this->error("Failed to create Square invoice: {$e->getMessage()}");
            $this->newLine();
            $this->line("Error details:");
            $this->line($e->getTraceAsString());
        }
    }
}
